<?php

namespace App\Http\Controllers\Admins;

use App\Helpers\CategoryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ActivityLogService;
use App\Services\Admin\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
        protected ActivityLogService $activityLogService
    ) {
        // Authorization is handled in each method individually
    }

    /**
     * Display a listing of categories
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query()->with(['parent', 'children']);

        // Search
        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%');
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by parent (only root categories)
        if ($request->get('only_root') === '1') {
            $query->whereNull('parent_id');
        }

        // Filter by parent_id
        if ($parentId = $request->get('parent_id')) {
            $query->where('parent_id', $parentId);
        } else {
            $parentId = null;
        }

        // Sort
        $sortBy = $request->get('sort_by', 'order');
        $sortDir = $request->get('sort_dir', 'asc');

        if ($sortBy === 'name') {
            $query->orderBy('name', $sortDir);
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', $sortDir);
        } else {
            $query->orderBy('order', $sortDir);
        }

        $query->orderBy('name', 'asc');

        // Pagination
        $perPage = (int) $request->get('per_page', 50);
        $perPage = in_array($perPage, [50, 100]) ? $perPage : 50;

        $categories = $query->paginate($perPage)->appends($request->query());

        // Get tree for sidebar
        $tree = CategoryHelper::buildTree(null, true);

        return view('admins.categories.index', compact('categories', 'tree', 'parentId'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create(): View
    {
        $this->authorize('create', Category::class);

        $category = new Category;
        $parentOptions = CategoryHelper::getDropdownOptions();

        return view('admins.categories.form', compact('category', 'parentOptions'));
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        try {
            $data = $request->validated();

            // Normalize parent_id: empty string should be null for root categories
            if (isset($data['parent_id']) && ($data['parent_id'] === '' || $data['parent_id'] === 0)) {
                $data['parent_id'] = null;
            }

            $image = $request->hasFile('image') ? $request->file('image') : null;

            $category = $this->categoryService->create($data, $image);

            // Log activity
            $this->activityLogService->logCreate($category, 'Tạo danh mục mới: '.$category->name);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Tạo danh mục thành công.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified category
     */
    public function show(Category $category): RedirectResponse
    {
        $this->authorize('view', $category);

        // Redirect to edit page instead of show page
        return redirect()->route('admin.categories.edit', $category);
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        $parentOptions = CategoryHelper::getDropdownOptions($category->id);
        $breadcrumb = $this->categoryService->getBreadcrumb($category);

        // Decode metadata if exists
        if ($category->metadata && is_string($category->metadata)) {
            $category->metadata = json_decode($category->metadata, true);
        }

        return view('admins.categories.form', compact('category', 'parentOptions', 'breadcrumb'));
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        try {
            $data = $request->validated();

            // Normalize parent_id: empty string should be null for root categories
            if (isset($data['parent_id']) && ($data['parent_id'] === '' || $data['parent_id'] === 0)) {
                $data['parent_id'] = null;
            }

            $image = $request->hasFile('image') ? $request->file('image') : null;
            $deleteOldImage = $request->boolean('delete_image', false);

            // Check permissions for specific fields
            if (isset($data['slug']) && ! Gate::allows('changeSlug', $category)) {
                unset($data['slug']);
            }

            if (isset($data['parent_id']) && ! Gate::allows('changeParent', $category)) {
                unset($data['parent_id']);
            }

            $oldData = $category->toArray();
            $this->categoryService->update($category, $data, $image, $deleteOldImage);

            // Log activity
            $this->activityLogService->logUpdate($category->fresh(), $oldData, 'Cập nhật danh mục: '.$category->name);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Cập nhật danh mục thành công.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update parent category
     */
    public function updateParent(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        // Protect default category (id = 1)
        if ($category->id === 1) {
            return back()->withErrors(['error' => 'Không thể thay đổi danh mục cha của danh mục mặc định (ID: 1).']);
        }

        $request->validate([
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($category) {
                    // Allow empty string, 0, or null for root categories
                    if ($value === '' || $value === 0 || $value === null) {
                        return;
                    }
                    // If value is provided, it must be a valid category ID
                    if (! Category::where('id', $value)->exists()) {
                        $fail('Danh mục cha không tồn tại.');
                    }
                    // Check circular reference
                    if (! CategoryHelper::canMoveToParent($category->id, $value)) {
                        $fail('Không thể di chuyển danh mục thành con của chính nó hoặc con của nó.');
                    }
                },
            ],
        ]);

        try {
            $parentId = $request->input('parent_id');
            if ($parentId === '' || $parentId === 0) {
                $parentId = null;
            }

            $category->update(['parent_id' => $parentId]);

            return back()->with('success', 'Đã cập nhật danh mục cha thành công.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        try {
            $forceDeleteTree = $request->boolean('force_delete_tree', false);

            if ($forceDeleteTree && ! Gate::allows('deleteTree', $category)) {
                return back()->withErrors(['error' => 'Bạn không có quyền xóa cả cây danh mục.']);
            }

            // Log activity before delete
            $this->activityLogService->logDelete($category, 'Xóa danh mục: '.$category->name);

            $this->categoryService->delete($category, $forceDeleteTree);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Xóa danh mục thành công.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Toggle category status
     */
    public function toggleStatus(Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        // Protect default category (id = 1) - cannot change status
        if ($category->id === 1) {
            return back()->withErrors(['error' => 'Không thể thay đổi trạng thái danh mục mặc định (ID: 1). Đây là danh mục hệ thống.']);
        }

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', 'Đã cập nhật trạng thái danh mục.');
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Category::class);

        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:categories,id'],
            'bulk_action' => ['required', 'in:hide,show,delete'],
        ]);

        $ids = $request->input('selected', []);
        $action = $request->input('bulk_action');

        if ($action === 'hide') {
            // Exclude default category (id = 1) from bulk hide
            $ids = array_filter($ids, fn ($id) => $id != 1);
            if (empty($ids)) {
                return back()->withErrors(['error' => 'Không thể ẩn danh mục mặc định (ID: 1).']);
            }
            Category::whereIn('id', $ids)->update(['is_active' => false]);

            return back()->with('success', 'Đã ẩn '.count($ids).' danh mục.');
        }

        if ($action === 'show') {
            Category::whereIn('id', $ids)->update(['is_active' => true]);

            return back()->with('success', 'Đã hiển thị '.count($ids).' danh mục.');
        }

        if ($action === 'delete') {
            // Exclude default category (id = 1) from bulk delete
            $ids = array_filter($ids, fn ($id) => $id != 1);
            if (empty($ids)) {
                return back()->withErrors(['error' => 'Không thể xóa danh mục mặc định (ID: 1).']);
            }

            $deleted = 0;
            $errors = [];

            foreach ($ids as $id) {
                try {
                    $category = Category::findOrFail($id);
                    if (Gate::allows('delete', $category)) {
                        $this->categoryService->delete($category);
                        $deleted++;
                    } else {
                        $errors[] = "Không có quyền xóa danh mục: {$category->name}";
                    }
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }

            $message = "Đã xóa {$deleted} danh mục.";
            if (! empty($errors)) {
                $message .= ' Lỗi: '.implode(', ', $errors);
            }

            return back()->with('success', $message);
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request): JsonResponse
    {
        if (! Gate::allows('reorder', Category::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', 'exists:categories,id'],
            'order.*.order' => ['required', 'integer', 'min:0'],
            'order.*.parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        try {
            $this->categoryService->reorder($request->input('order'));

            return response()->json([
                'success' => true,
                'message' => 'Đã sắp xếp lại danh mục thành công.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category tree (API)
     */
    public function tree(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive', false);
        $tree = $this->categoryService->getTree($includeInactive);

        return response()->json([
            'success' => true,
            'tree' => $tree,
        ]);
    }

    /**
     * Get category info (API)
     */
    public function apiShow(Category $category): JsonResponse
    {
        $category->load(['parent', 'children']);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }
}
