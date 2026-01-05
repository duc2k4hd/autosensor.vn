<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAutosaveRequest;
use App\Http\Requests\Admin\PostStoreRequest;
use App\Http\Requests\Admin\PostUpdateRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Services\Admin\PostService;
use App\Services\SeoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected SeoService $seoService,
    ) {
        // Middleware được đăng ký trong routes/admin.php
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Post::class);

        $query = Post::query()
            ->with(['author', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('author_id'), fn ($q) => $q->where('created_by', $request->integer('author_id')))
            ->when($request->filled('tag_id'), function ($q) use ($request) {
                $tagId = $request->integer('tag_id');
                // Tìm posts có tag với entity_type = Post::class
                $q->whereHas('tags', function ($tagQuery) use ($tagId) {
                    $tagQuery->where('tags.id', $tagId);
                });
            })
            ->when($request->filled('is_featured'), fn ($q) => $q->where('is_featured', $request->boolean('is_featured')))
            ->when($request->filled('without_images'), function ($q) {
                $q->whereNull('image_ids')
                    ->orWhereJsonLength('image_ids', 0);
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('published_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('published_at', '<=', $request->date('date_to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $keyword = $request->input('search');
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('title', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'));

        $posts = $query->paginate(20)->withQueryString();

        return view('admins.posts.index', [
            'posts' => $posts,
            'filters' => $request->all(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::where('entity_type', Post::class)->select('id', 'name')->distinct('name')->orderBy('name')->get()->unique('name')->values(),
            'authors' => Account::orderBy('name')->get(['id', 'name', 'email']),
            'statusOptions' => [
                'draft' => 'Nháp',
                'pending' => 'Chờ duyệt',
                'published' => 'Đã xuất bản',
                'archived' => 'Lưu trữ',
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Post::class);

        $post = new Post;
        $post->setRelation('revisions', collect());

        // Chỉ lấy tags của posts (entity_type = Post::class), không lấy tags của products
        $postOnlyTags = Tag::where('entity_type', Post::class)
            ->select('id', 'name')
            ->distinct('name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return view('admins.posts.create', [
            'post' => $post,
            'categories' => Category::orderBy('name')->get(),
            'tags' => $postOnlyTags, // Chỉ tags của posts
            'postTags' => collect(), // Chưa có tags khi tạo mới
            'mediaPicker' => $this->mediaPickerConfig(),
        ]);
    }

    public function store(PostStoreRequest $request): RedirectResponse
    {
        $post = $this->postService->create($request->validated(), $request->user('web'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Đã tạo bài viết.');
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        $post->load([
            'revisions' => fn ($q) => $q->latest()->limit(10),
            'author',
            'category',
        ]);

        // Load tags từ relationship (entity_type = Post::class)
        $postTags = $post->tags()->get();

        // Lấy tất cả tags của posts (entity_type = Post::class), không lấy tags của products
        // Bao gồm cả tags của post này để đảm bảo hiển thị đầy đủ
        $postTagIds = $postTags->pluck('id')->toArray();

        // Lấy tags của post này
        $postSpecificTags = Tag::where('entity_type', Post::class)
            ->whereIn('id', $postTagIds)
            ->select('id', 'name')
            ->get();

        // Lấy các tags khác (distinct theo name) để hiển thị trong dropdown
        $otherTags = Tag::where('entity_type', Post::class)
            ->whereNotIn('id', $postTagIds)
            ->select('id', 'name')
            ->distinct('name')
            ->orderBy('name')
            ->get()
            ->unique('name');

        // Gộp lại và unique theo ID để giữ tất cả tags của post này
        $postOnlyTags = $postSpecificTags->merge($otherTags)->unique('id')->values();

        return view('admins.posts.edit', [
            'post' => $post,
            'categories' => Category::orderBy('name')->get(),
            'tags' => $postOnlyTags, // Chỉ tags của posts
            'postTags' => $postTags, // Tags đã gắn với post này
            'authors' => Account::orderBy('name')->get(['id', 'name', 'email']),
            'seoInsights' => $this->seoService->evaluateSeoScore($post),
            'mediaPicker' => $this->mediaPickerConfig(),
        ]);
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $post = $this->postService->update($post, $request->validated(), $request->user('web'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->postService->delete($post);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã xóa bài viết.');
    }

    public function restore(int $postId): RedirectResponse
    {
        $post = Post::withTrashed()->findOrFail($postId);
        $this->authorize('restore', $post);

        $post->restore();

        return back()->with('success', 'Đã khôi phục bài viết.');
    }

    public function publish(Post $post, Request $request): RedirectResponse
    {
        $this->authorize('update', $post);

        $schedule = $request->input('published_at');
        $scheduleAt = null;

        if ($schedule) {
            try {
                $scheduleAt = Carbon::parse($schedule);
            } catch (\Throwable $e) {
                $scheduleAt = null;
            }
        }

        $this->postService->update($post, [
            'status' => 'published',
            'published_at' => $scheduleAt?->toDateTimeString(),
        ], $request->user('web'));

        return back()->with('success', 'Đã cập nhật trạng thái bài viết.');
    }

    public function archive(Post $post, Request $request): RedirectResponse
    {
        $this->authorize('update', $post);

        $this->postService->update($post, ['status' => 'archived'], $request->user('web'));

        return back()->with('success', 'Đã lưu trữ bài viết.');
    }

    public function duplicate(Post $post, Request $request): RedirectResponse
    {
        $this->authorize('view', $post);

        $clone = $this->postService->duplicate($post, $request->user('web'));

        return redirect()->route('admin.posts.edit', $clone)
            ->with('success', 'Đã nhân bản bài viết.');
    }

    public function feature(Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update(['is_featured' => true]);

        return back()->with('success', 'Đã bật nổi bật.');
    }

    public function unfeature(Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update(['is_featured' => false]);

        return back()->with('success', 'Đã tắt nổi bật.');
    }

    public function revisions(Post $post): JsonResponse
    {
        return response()->json([
            'data' => $post->revisions()->latest()->limit(20)->get(),
        ]);
    }

    public function autosave(PostAutosaveRequest $request, Post $post): JsonResponse
    {
        $revision = $this->postService->autosave($post, $request->validated(), $request->user('web'));

        return response()->json([
            'success' => true,
            'revision_id' => $revision->id,
            'saved_at' => $revision->created_at,
        ]);
    }

    public function restoreRevision(Post $post, int $revisionId, Request $request): RedirectResponse
    {
        $revision = PostRevision::where('post_id', $post->id)->findOrFail($revisionId);

        $this->postService->restoreRevision($post, $revision, $request->user('web'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Đã khôi phục phiên bản bản thảo.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:webp,jpg,jpeg,png,gif', 'max:5120'], // 5MB
        ]);

        try {
            $file = $request->file('image');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());

            // Generate unique filename
            $slugName = \Illuminate\Support\Str::slug($originalName);
            $destination = public_path('clients/assets/img/posts');
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            // Convert to webp if possible
            $finalExtension = $extension;
            $finalName = $slugName.'-'.time().'.'.$finalExtension;

            // Check if file exists, add counter
            $counter = 1;
            while (file_exists($destination.'/'.$finalName)) {
                $finalName = $slugName.'-'.time().'-'.$counter.'.'.$finalExtension;
                $counter++;
            }

            $targetPath = $destination.'/'.$finalName;

            // Convert to webp if image and WebP is supported
            if (in_array($extension, ['jpg', 'jpeg', 'png']) && function_exists('imagewebp')) {
                $finalExtension = 'webp';
                $finalName = pathinfo($finalName, PATHINFO_FILENAME).'.webp';
                $targetPath = $destination.'/'.$finalName;

                // Use FileManager to convert
                $fileManager = app(\App\Services\Media\FileManager::class);
                $converted = $fileManager->convertToWebp($file->getRealPath(), $targetPath, 2048);

                if (! $converted || ! file_exists($targetPath)) {
                    // Fallback: keep original format
                    $finalExtension = $extension;
                    $finalName = pathinfo($finalName, PATHINFO_FILENAME).'.'.$extension;
                    $targetPath = $destination.'/'.$finalName;
                    $file->move($destination, $finalName);
                }
            } else {
                // Keep original format
                $file->move($destination, $finalName);
            }

            // Create Image record in database
            $image = \App\Models\Image::create([
                'url' => $finalName,
                'title' => $originalName,
                'alt' => $originalName,
                'is_primary' => false,
                'order' => 0,
            ]);

            // Build full URL
            $baseUrl = config('app.url');
            $fullUrl = rtrim($baseUrl, '/').'/clients/assets/img/posts/'.$finalName;

            return response()->json([
                'success' => true,
                'filename' => $finalName,
                'url' => $fullUrl,
                'image_id' => $image->id,
                'image' => [
                    'id' => $image->id,
                    'url' => $finalName,
                    'title' => $image->title,
                    'alt' => $image->alt,
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PostController uploadImage error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể upload ảnh: '.$e->getMessage(),
            ], 500);
        }
    }

    private function mediaPickerConfig(): array
    {
        return [
            'title' => 'Chọn ảnh từ thư viện',
            'scope' => 'client',
            'folder' => 'posts',
            'per_page' => 100,
            'list_url' => route('admin.media.list'),
            'upload_url' => route('admin.media.upload'),
        ];
    }
}
