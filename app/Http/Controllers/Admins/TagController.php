<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagStoreRequest;
use App\Http\Requests\Admin\TagUpdateRequest;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {}

    /**
     * Danh sách tags
     */
    public function index(Request $request): View
    {
        $query = Tag::with('entity')
            ->filter($request->all())
            ->orderByDesc('created_at');

        $tags = $query->paginate(20)->withQueryString();

        // Lấy danh sách entity types (dùng string để dễ xử lý trong JavaScript)
        $entityTypes = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
        ];

        // Load current entity nếu có entity_id và entity_type trong request
        $currentEntity = null;
        if ($request->filled('entity_id') && $request->filled('entity_type')) {
            $entityType = $request->entity_type;
            if ($entityType === 'product') {
                $currentEntity = Product::find($request->entity_id);
            } elseif ($entityType === 'post') {
                $currentEntity = Post::find($request->entity_id);
            }
        }

        return view('admins.tags.index', [
            'tags' => $tags,
            'filters' => $request->all(),
            'entityTypes' => $entityTypes,
            'currentEntity' => $currentEntity,
        ]);
    }

    /**
     * Form tạo tag
     */
    public function create(): View
    {
        $entityTypes = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
        ];

        // Load entities nếu có entity_type trong request
        $entities = collect();
        if (request()->has('entity_type')) {
            $entityType = request()->get('entity_type');
            if ($entityType === 'product') {
                $entities = Product::query()->limit(100)->get(['id', 'name']);
            } elseif ($entityType === 'post') {
                $entities = Post::query()->limit(100)->get(['id', 'title as name']);
            }
        }

        return view('admins.tags.create', [
            'tag' => new Tag,
            'entityTypes' => $entityTypes,
            'entities' => $entities,
        ]);
    }

    /**
     * Lưu tag mới
     */
    public function store(TagStoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Normalize entity_type
            if ($data['entity_type'] === 'product') {
                $data['entity_type'] = Product::class;
            } elseif ($data['entity_type'] === 'post') {
                $data['entity_type'] = Post::class;
            }

            $tag = $this->tagService->create($data);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã tạo tag thành công');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể tạo tag: '.$e->getMessage());
        }
    }

    /**
     * Form chỉnh sửa tag
     */
    public function edit(Tag $tag): View
    {
        $entityTypes = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
        ];

        // Normalize entity_type để hiển thị
        $tag->entity_type_display = $tag->entity_type;
        if ($tag->entity_type === Product::class) {
            $tag->entity_type_display = 'product';
        } elseif ($tag->entity_type === Post::class) {
            $tag->entity_type_display = 'post';
        }

        // Load entities cho entity_type hiện tại
        $entities = collect();
        if ($tag->entity_type === Product::class) {
            $entities = Product::query()->limit(100)->get(['id', 'name']);
        } elseif ($tag->entity_type === Post::class) {
            $entities = Post::query()->limit(100)->get(['id', 'title as name']);
        }

        return view('admins.tags.edit', [
            'tag' => $tag,
            'entityTypes' => $entityTypes,
            'entities' => $entities,
        ]);
    }

    /**
     * Cập nhật tag
     */
    public function update(TagUpdateRequest $request, Tag $tag): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Normalize entity_type
            if (isset($data['entity_type'])) {
                if ($data['entity_type'] === 'product') {
                    $data['entity_type'] = Product::class;
                } elseif ($data['entity_type'] === 'post') {
                    $data['entity_type'] = Post::class;
                }
            }

            $this->tagService->update($tag, $data);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã cập nhật tag thành công');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật tag: '.$e->getMessage());
        }
    }

    /**
     * Xóa tag
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        try {
            if ($tag->usage_count > 0) {
                return back()
                    ->with('error', 'Không thể xóa tag đang được sử dụng. Vui lòng chuyển sang inactive.');
            }

            $this->tagService->delete($tag);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã xóa tag thành công');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể xóa tag: '.$e->getMessage());
        }
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:tags,id',
        ]);

        try {
            $deleted = $this->tagService->deleteMultiple($request->ids);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', "Đã xóa {$deleted} tag thành công");
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể xóa tags: '.$e->getMessage());
        }
    }

    /**
     * Gộp tags
     */
    public function merge(Request $request): RedirectResponse
    {
        $request->validate([
            'source_id' => 'required|integer|exists:tags,id',
            'target_id' => 'required|integer|exists:tags,id',
        ]);

        try {
            $sourceTag = Tag::findOrFail($request->source_id);
            $targetTag = Tag::findOrFail($request->target_id);

            $this->tagService->merge($sourceTag, $targetTag);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã gộp tags thành công');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể gộp tags: '.$e->getMessage());
        }
    }

    /**
     * Gợi ý tags (AJAX)
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'entity_type' => 'nullable|string',
        ]);

        $suggestions = $this->tagService->suggest(
            $request->keyword,
            $request->entity_type,
            10
        );

        return response()->json($suggestions);
    }

    /**
     * Gợi ý tags từ content (AJAX)
     */
    public function suggestFromContent(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'entity_type' => 'nullable|string',
        ]);

        $suggestions = $this->tagService->suggestFromContent(
            $request->content,
            $request->entity_type,
            5
        );

        return response()->json($suggestions);
    }

    /**
     * Lấy danh sách entities để select
     */
    public function getEntities(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string',
            'keyword' => 'nullable|string|max:255',
            'id' => 'nullable|integer', // Để load entity cụ thể khi đã chọn
            'limit' => 'nullable|integer|min:1|max:100', // Giới hạn số lượng kết quả
        ]);

        $entityType = $request->entity_type;
        if ($entityType === 'product') {
            $entityType = Product::class;
        } elseif ($entityType === 'post') {
            $entityType = Post::class;
        }

        $limit = $request->input('limit', 100); // Mặc định 100, tối đa 100

        $query = null;
        if ($entityType === Product::class) {
            $query = Product::query();

            // Nếu có id cụ thể, load entity đó
            if ($request->filled('id')) {
                $query->where('id', $request->id);
            } elseif ($request->filled('keyword')) {
                // Search khi có keyword, giới hạn theo limit
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->keyword}%")
                        ->orWhere('sku', 'like', "%{$request->keyword}%");
                })
                    ->limit($limit);
            } else {
                // Không có keyword và không có id -> load 10 entities đầu tiên
                $query->limit(10);
            }

            $entities = $query->get(['id', 'name', 'sku']);
            // Format để có field 'name' và 'sku'
            $entities = $entities->map(function ($entity) {
                return [
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'sku' => $entity->sku,
                ];
            });
        } elseif ($entityType === Post::class) {
            $query = Post::query();

            // Nếu có id cụ thể, load entity đó
            if ($request->filled('id')) {
                $query->where('id', $request->id);
            } elseif ($request->filled('keyword')) {
                // Search khi có keyword, giới hạn theo limit
                $query->where('title', 'like', "%{$request->keyword}%")
                    ->limit($limit);
            } else {
                // Không có keyword và không có id -> load 10 entities đầu tiên
                $query->limit(10);
            }

            $entities = $query->get(['id', 'title']);
            // Format để có field 'name'
            $entities = $entities->map(function ($entity) {
                return [
                    'id' => $entity->id,
                    'name' => $entity->title,
                    'sku' => null,
                ];
            });
        } else {
            $entities = collect();
        }

        return response()->json($entities);
    }
}
