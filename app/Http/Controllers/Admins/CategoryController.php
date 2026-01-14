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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

            // Clear cache khi tạo category mới
            Cache::forget('admin_categories_active');
            Cache::forget('import_categories_active');

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
            $decoded = json_decode((string) $category->metadata, true);
            $category->metadata = is_array($decoded) ? $decoded : null;
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

            // Clear cache khi cập nhật category
            Cache::forget('admin_categories_active');
            Cache::forget('import_categories_active');

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

            // Clear cache khi xóa category
            Cache::forget('admin_categories_active');
            Cache::forget('import_categories_active');

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

    /**
     * Export categories ra file Excel
     * Format: id, parent_slug, name, slug, description, image, order, is_active, 
     *         meta_title, meta_description, meta_keywords, meta_canonical, created_at, updated_at
     * Metadata được tách thành các cột riêng để dễ quản lý
     */
    public function exportCategories()
    {
        try {
            // Kiểm tra authorization
            $this->authorize('viewAny', Category::class);
            
            Log::info('Export categories - Starting', [
                'user_id' => Auth::id(),
            ]);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Categories');

            // Headers - tách metadata thành các cột riêng
            $headers = [
                'ID',
                'Parent Slug',
                'Name',
                'Slug',
                'Description',
                'Image',
                'Order',
                'Is Active',
                'Meta Title',
                'Meta Description',
                'Meta Keywords',
                'Meta Canonical',
                'Created At',
                'Updated At',
            ];
            $sheet->fromArray([$headers], null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ];
            $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

            // Get all categories với parent
            $categories = Category::with('parent')->orderBy('order')->orderBy('name')->get();

            $row = 2;
            foreach ($categories as $category) {
                // Decode metadata nếu là string
                $metadata = $category->metadata;
                if (is_string($metadata)) {
                    $decoded = json_decode($metadata, true);
                    $metadata = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($metadata)) {
                    $metadata = [];
                }
                
                $sheet->setCellValue('A'.$row, $category->id);
                $sheet->setCellValue('B'.$row, $category->parent ? $category->parent->slug : '');
                $sheet->setCellValue('C'.$row, $category->name);
                $sheet->setCellValue('D'.$row, $category->slug);
                $sheet->setCellValue('E'.$row, $category->description ?? '');
                $sheet->setCellValue('F'.$row, $category->image ?? '');
                $sheet->setCellValue('G'.$row, $category->order);
                $sheet->setCellValue('H'.$row, $category->is_active ? 'Yes' : 'No');
                // Tách metadata thành các cột riêng
                $sheet->setCellValue('I'.$row, $metadata['meta_title'] ?? '');
                $sheet->setCellValue('J'.$row, $metadata['meta_description'] ?? '');
                $sheet->setCellValue('K'.$row, $metadata['meta_keywords'] ?? '');
                $sheet->setCellValue('L'.$row, $metadata['meta_canonical'] ?? '');
                $sheet->setCellValue('M'.$row, $category->created_at ? $category->created_at->format('Y-m-d H:i:s') : '');
                $sheet->setCellValue('N'.$row, $category->updated_at ? $category->updated_at->format('Y-m-d H:i:s') : '');
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $fileName = 'categories_export_'.date('Y-m-d_His').'.xlsx';

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Throwable $e) {
            Log::error('Export categories error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Trả về response lỗi dạng JSON hoặc redirect
            // Vì return type là StreamedResponse, nên phải throw exception
            // Laravel sẽ xử lý và hiển thị error page
            abort(500, 'Lỗi export: '.$e->getMessage());
        }
    }

    /**
     * Import categories từ file Excel
     * Logic thông minh: Update nếu slug đã tồn tại, tạo mới nếu chưa có
     * Xử lý parent_id thông qua parent_slug
     */
    public function importCategories(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $errors = [];
            $created = 0;
            $updated = 0;
            $skipped = 0;

            // Cache để tránh query lặp lại
            $categoryCache = [];
            $slugToIdMap = [];

            // Pre-load tất cả categories để cache
            $allCategories = Category::all();
            foreach ($allCategories as $cat) {
                $categoryCache[$cat->slug] = $cat;
                $slugToIdMap[$cat->slug] = $cat->id;
            }

            DB::beginTransaction();

            // Bỏ qua header (row 1)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $id = trim($sheet->getCell("A{$row}")->getValue() ?? '');
                    $parentSlug = trim($sheet->getCell("B{$row}")->getValue() ?? '');
                    $name = trim($sheet->getCell("C{$row}")->getValue() ?? '');
                    $slug = trim($sheet->getCell("D{$row}")->getValue() ?? '');
                    $description = trim($sheet->getCell("E{$row}")->getValue() ?? '');
                    $image = trim($sheet->getCell("F{$row}")->getValue() ?? '');
                    $order = (int) ($sheet->getCell("G{$row}")->getValue() ?? 0);
                    $isActiveStr = trim($sheet->getCell("H{$row}")->getValue() ?? 'Yes');
                    // Đọc metadata từ các cột riêng
                    $metaTitle = trim($sheet->getCell("I{$row}")->getValue() ?? '');
                    $metaDescription = trim($sheet->getCell("J{$row}")->getValue() ?? '');
                    $metaKeywords = trim($sheet->getCell("K{$row}")->getValue() ?? '');
                    $metaCanonical = trim($sheet->getCell("L{$row}")->getValue() ?? '');
                    $createdAt = trim($sheet->getCell("M{$row}")->getValue() ?? '');
                    $updatedAt = trim($sheet->getCell("N{$row}")->getValue() ?? '');

                    // Validate required fields
                    if (empty($name) || empty($slug)) {
                        $skipped++;
                        $errors[] = [
                            'row' => $row,
                            'message' => 'Thiếu name hoặc slug',
                        ];
                        continue;
                    }

                    // Xử lý parent_id từ parent_slug
                    $parentId = null;
                    if (!empty($parentSlug)) {
                        if (isset($slugToIdMap[$parentSlug])) {
                            $parentId = $slugToIdMap[$parentSlug];
                        } else {
                            // Nếu parent chưa tồn tại, tạo parent trước (nếu cần)
                            // Hoặc bỏ qua và log lỗi
                            $errors[] = [
                                'row' => $row,
                                'message' => "Parent slug '{$parentSlug}' không tồn tại",
                            ];
                            // Tiếp tục xử lý category này nhưng không có parent
                        }
                    }

                    // Xử lý is_active
                    $isActive = in_array(strtolower($isActiveStr), ['yes', '1', 'true', 'active']) ? true : false;

                    // Xử lý metadata: merge các cột metadata thành JSON
                    $metadata = [];
                    if (!empty($metaTitle)) {
                        $metadata['meta_title'] = $metaTitle;
                    }
                    if (!empty($metaDescription)) {
                        $metadata['meta_description'] = $metaDescription;
                    }
                    if (!empty($metaKeywords)) {
                        $metadata['meta_keywords'] = $metaKeywords;
                    }
                    if (!empty($metaCanonical)) {
                        $metadata['meta_canonical'] = $metaCanonical;
                    }
                    // Chỉ lưu metadata nếu có ít nhất 1 field
                    $metadata = !empty($metadata) ? $metadata : null;

                    // Xử lý timestamps (nếu có)
                    $createdAtValue = null;
                    $updatedAtValue = null;
                    if (!empty($createdAt)) {
                        try {
                            $createdAtValue = \Carbon\Carbon::parse($createdAt);
                        } catch (\Exception $e) {
                            // Bỏ qua nếu không parse được
                        }
                    }
                    if (!empty($updatedAt)) {
                        try {
                            $updatedAtValue = \Carbon\Carbon::parse($updatedAt);
                        } catch (\Exception $e) {
                            // Bỏ qua nếu không parse được
                        }
                    }

                    // Tìm category theo slug (không dùng ID vì có thể thay đổi)
                    $category = $categoryCache[$slug] ?? null;

                    $categoryData = [
                        'parent_id' => $parentId,
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description ?: null,
                        'image' => $image ?: null,
                        'order' => $order,
                        'is_active' => $isActive,
                        'metadata' => $metadata,
                    ];

                    if ($category) {
                        // Update category hiện có
                        $category->update($categoryData);
                        
                        // Update timestamps nếu có
                        if ($createdAtValue) {
                            $category->created_at = $createdAtValue;
                        }
                        if ($updatedAtValue) {
                            $category->updated_at = $updatedAtValue;
                        }
                        $category->save();
                        
                        $updated++;
                    } else {
                        // Tạo category mới
                        if ($createdAtValue) {
                            $categoryData['created_at'] = $createdAtValue;
                        }
                        if ($updatedAtValue) {
                            $categoryData['updated_at'] = $updatedAtValue;
                        }
                        
                        $category = Category::create($categoryData);
                        $categoryCache[$slug] = $category;
                        $slugToIdMap[$slug] = $category->id;
                        
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $row,
                        'message' => $e->getMessage(),
                    ];
                    Log::error('Import category error', [
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            // Clear cache
            Cache::forget('categories_tree');
            Cache::forget('categories_active');

            $message = "Import thành công! Tạo mới: {$created}, Cập nhật: {$updated}, Bỏ qua: {$skipped}";
            if (!empty($errors)) {
                $message .= ". Có ".count($errors)." lỗi.";
            }

            return redirect()->back()
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import categories system error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Lỗi import: '.$e->getMessage());
        }
    }
}
