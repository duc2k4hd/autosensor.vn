<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $postsQuery = Post::query()
            ->published()
            ->select(['id', 'title', 'slug', 'excerpt', 'image_ids', 'category_id', 'published_at', 'views', 'created_at'])
            ->with(['category:id,name,slug']);

        $activeCategory = null;
        if ($categorySlug = $request->query('category')) {
            $activeCategory = Cache::remember('blog_cat_'.$categorySlug, now()->addDays(7), function() use ($categorySlug) {
                return Category::where('slug', $categorySlug)->select(['id', 'name', 'slug'])->first();
            });

            if (! $activeCategory) {
                abort(404);
            }

            $postsQuery->where('category_id', $activeCategory->id);
        }

        $activeTags = collect();
        $shouldNoindex = false;

        // Xử lý tags mới: tags=slug1,slug2
        if ($request->has('tags')) {
            $shouldNoindex = true;
            $tagsInput = $request->input('tags');

            // Nếu là array, chuyển thành string
            if (is_array($tagsInput)) {
                $tagsInput = implode(',', $tagsInput);
            }

            // Tách các tags
            $tagSlugs = array_filter(array_map('trim', explode(',', (string) $tagsInput)));

            if (! empty($tagSlugs)) {
                $activeTags = Tag::query()
                    ->whereIn('slug', $tagSlugs)
                    ->where('entity_type', Post::class)
                    ->where('is_active', true)
                    ->get();

                if ($activeTags->isNotEmpty()) {
                    $tagIds = $activeTags->pluck('id')->toArray();
                    $postsQuery->where(function ($query) use ($tagIds) {
                        foreach ($tagIds as $tagId) {
                            $query->orWhereJsonContains('tag_ids', (int) $tagId)
                                ->orWhereJsonContains('tag_ids', (string) $tagId);
                        }
                    });
                }
            }
        }

        // Xử lý tag cũ (backward compatibility): tag=slug
        if ($request->has('tag') && ! $request->has('tags')) {
            $shouldNoindex = true;
            $tagSlug = trim((string) $request->query('tag'));

            if ($tagSlug !== '') {
                $activeTag = Tag::query()
                    ->where('slug', $tagSlug)
                    ->where('entity_type', Post::class)
                    ->first();

                if ($activeTag) {
                    $activeTags = collect([$activeTag]);
                    $postsQuery->where(function ($query) use ($activeTag) {
                        $query->whereJsonContains('tag_ids', (int) $activeTag->id)
                            ->orWhereJsonContains('tag_ids', (string) $activeTag->id);
                    });
                }
            }
        }

        if ($searchTerm = trim((string) $request->query('q'))) {
            $postsQuery->where(function ($query) use ($searchTerm) {
                $query->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        $posts = $postsQuery
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->simplePaginate(12)
            ->withQueryString();

        // Cache tổng số bài viết để tránh COUNT(*) mỗi lần load trang
        $totalPosts = Cache::remember('blog_total_posts', now()->addHours(1), function() {
            return Post::published()->count();
        });
        
        Post::preloadImages($posts->getCollection());

        // Bundle Sidebar Cache - Gộp 5 query thành 1 lời gọi Redis duy nhất
        $sidebarBundle = Cache::remember('blog_sidebar_bundle_v2', now()->addMinutes(30), function () {
            $data = [];
            
            // 1. Featured Posts
            $data['featuredPosts'] = Post::query()
                ->published()
                ->where('is_featured', true)
                ->select(['id', 'title', 'slug', 'excerpt', 'image_ids', 'category_id', 'published_at', 'views'])
                ->with(['category:id,name,slug'])
                ->orderByDesc('published_at')
                ->take(3)
                ->get();
            Post::preloadImages($data['featuredPosts']);

            // 2. Sidebar Categories
            $data['sidebarCategories'] = Category::query()
                ->withCount(['posts as posts_count' => fn($q) => $q->published()])
                ->having('posts_count', '>', 0)
                ->orderByDesc('posts_count')
                ->take(6)
                ->get(['id', 'name', 'slug']);

            // 3. Sidebar Tags
            $data['sidebarTags'] = Tag::query()
                ->where('entity_type', Post::class)
                ->where('is_active', true)
                ->orderByDesc('usage_count')
                ->take(12)
                ->get(['id', 'name', 'slug']);

            // 4. Recent Posts
            $data['recentPosts'] = Post::query()
                ->published()
                ->select(['id', 'title', 'slug', 'published_at'])
                ->orderByDesc('published_at')
                ->take(5)
                ->get();

            // 5. Popular Posts
            $data['popularPosts'] = Post::query()
                ->published()
                ->select(['id', 'title', 'slug', 'views'])
                ->orderByDesc('views')
                ->take(5)
                ->get();
                
            return $data;
        });

        $featuredPosts = $sidebarBundle['featuredPosts'];
        $sidebarCategories = $sidebarBundle['sidebarCategories'];
        $sidebarTags = $sidebarBundle['sidebarTags'];
        $recentPosts = $sidebarBundle['recentPosts'];
        $popularPosts = $sidebarBundle['popularPosts'];

        $schemaData = $this->buildIndexSchemas($posts->getCollection());
        $meta = $this->resolveIndexMeta($activeCategory, $activeTags->first(), $searchTerm ?? null);

        return view('clients.pages.blog.index', [
            'posts' => $posts,
            'totalPosts' => $totalPosts,
            'featuredPosts' => $featuredPosts,
            'sidebarCategories' => $sidebarCategories,
            'sidebarTags' => $sidebarTags,
            'recentPosts' => $recentPosts,
            'popularPosts' => $popularPosts,
            'schemaData' => $schemaData,
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTags->first(),
            'activeTags' => $activeTags,
            'searchTerm' => $searchTerm ?? null,
            'pageTitle' => $meta['title'],
            'pageDescription' => $meta['description'],
            'pageKeywords' => $meta['keywords'],
            'heroHeading' => $meta['heading'],
            'heroSubheading' => $meta['subheading'],
            'heroContextLabel' => $meta['contextLabel'],
            'shouldNoindex' => $shouldNoindex || ! empty($request->getQueryString()),
        ]);
    }

    public function show(Post $post)
    {
        if ($post->status !== 'published' || ($post->published_at && $post->published_at->isFuture())) {
            abort(404);
        }

        // Views increment outside bundle
        $post->increment('views');

        // Redis Baking - Bundle all processed data to achieve < 1ms
        $bundle = Cache::remember("blog_post_bundle_v5_{$post->id}", now()->addDays(7), function () use ($post) {
            $data = [];
            
            // 1. Load Relations & Images Pool
            $post->loadMissing([
                'category:id,name,slug', 
                'creator:id,name',
            ]);
            Post::preloadImages([$post]);
            
            // 2. Content Processing (TOC)
            $contentData = $this->buildContentAnchors($post->content);
            $data['toc'] = $contentData['toc'];
            $data['contentWithAnchors'] = $contentData['content'];

            // 3. Images for View (Gallery & First)
            $data['galleryImages'] = $post->images;
            $data['firstImage'] = $post->coverImagePath() ? asset($post->coverImagePath()) : asset('clients/assets/img/posts/no-image.webp');

            // 4. Tags & Meta (Baked)
            $tags = $this->resolveTags($post);
            $data['tags'] = $tags;
            $data['meta'] = $this->resolvePostMeta($post);

            // 4. Related Posts (Selective Columns)
            $publishedAt = $post->published_at ?? $post->created_at;
            $categoryId = $post->category_id;

            $nextPosts = Post::query()
                ->published()
                ->where('id', '!=', $post->id)
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->where(function($q) use ($publishedAt, $post) {
                    $q->where('published_at', '>', $publishedAt)
                      ->orWhere(function($sub) use ($publishedAt, $post) {
                          $sub->where('published_at', $publishedAt)
                              ->where('id', '>', $post->id);
                      });
                })
                ->select(['id', 'title', 'slug', 'published_at', 'image_ids'])
                ->orderBy('published_at', 'asc')
                ->orderBy('id', 'asc')
                ->take(3)
                ->get();

            $prevPosts = Post::query()
                ->published()
                ->where('id', '!=', $post->id)
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->where(function($q) use ($publishedAt, $post) {
                    $q->where('published_at', '<', $publishedAt)
                      ->orWhere(function($sub) use ($publishedAt, $post) {
                          $sub->where('published_at', $publishedAt)
                              ->where('id', '<', $post->id);
                      });
                })
                ->select(['id', 'title', 'slug', 'published_at', 'image_ids'])
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->take(3)
                ->get();

            $merged = $prevPosts->reverse()->merge($nextPosts);

            if ($merged->count() < 6) {
                $excludeIds = $merged->pluck('id')->push($post->id)->all();
                $limit = 6 - $merged->count();
                $extraPosts = Post::query()
                    ->published()
                    ->whereNotIn('id', $excludeIds)
                    ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                    ->select(['id', 'title', 'slug', 'published_at', 'image_ids'])
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
                $merged = $merged->merge($extraPosts)->sortBy('published_at')->values();
            }
            Post::preloadImages($merged);
            $data['relatedPosts'] = $merged;

            // 5. Internal Links (Selective)
            $data['internalLinks'] = Post::query()
                ->published()
                ->where('id', '!=', $post->id)
                ->select(['id', 'title', 'slug', 'image_ids'])
                ->inRandomOrder()
                ->take(3)
                ->get();
            Post::preloadImages($data['internalLinks']);

            // 6. Comments & Ratings Bundle
            $comments = Comment::where('comments.commentable_type', 'post')
                ->where('comments.commentable_id', $post->id)
                ->whereNull('comments.parent_id')
                ->approved()
                ->with(['account:id,name,role'])
                ->orderByDesc('comments.created_at')
                ->limit(10)
                ->get();

            $commentIds = $comments->pluck('id');
            if ($commentIds->isNotEmpty()) {
                $adminReplies = Comment::query()
                    ->select('comments.*')
                    ->whereIn('comments.parent_id', $commentIds)
                    ->whereNotNull('comments.account_id')
                    ->join('accounts', 'comments.account_id', '=', 'accounts.id')
                    ->where('accounts.role', 'admin')
                    ->with('account:id,name,role')
                    ->get()
                    ->keyBy('parent_id');

                $comments->each(function ($comment) use ($adminReplies) {
                    if ($adminReplies->has($comment->id)) {
                        $comment->setRelation('adminReply', $adminReplies->get($comment->id));
                    }
                });
            }
            $data['comments'] = $comments;
            $data['totalComments'] = Comment::where('commentable_type', 'post')
                ->where('commentable_id', $post->id)
                ->whereNull('parent_id')
                ->approved()
                ->count();

            $commentService = app(\App\Services\CommentService::class);
            $data['ratingStats'] = $commentService->calculateRatingStats('post', $post->id);

            // 7. Schema (Final Baked)
            $data['schemaData'] = $this->buildShowSchemas($post, $tags);

            return $data;
        });

        return view('clients.pages.blog.show', array_merge($bundle, [
            'post' => $post,
            'pageTitle' => $bundle['meta']['title'],
            'pageDescription' => $bundle['meta']['description'],
            'pageKeywords' => $bundle['meta']['keywords'],
            'canonicalUrl' => $bundle['meta']['canonical'],
            'coverAsset' => $bundle['meta']['cover'],
        ]));
    }

    protected function buildIndexSchemas(Collection $posts): array
    {
        $indexUrl = route('client.blog.index');
        $siteName = config('app.name');

        $breadcrumb = $this->buildBreadcrumbSchema([
            [
                'name' => 'Trang chủ',
                'url' => route('client.home.index'),
            ],
            [
                'name' => 'Công nghệ & Tự động hóa',
                'url' => $indexUrl,
            ],
        ]);

        $latestPosts = $posts->take(6);
        $itemListElements = [];

        // Pre-calculate common values outside loop
        $noImageUrl = asset('clients/assets/img/posts/no-image.webp');

        foreach ($latestPosts as $index => $post) {
            $coverPath = $post->coverImagePath();
            $coverUrl = $coverPath ? asset($coverPath) : $noImageUrl;

            $itemListElements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'BlogPosting',
                    '@id' => route('client.blog.show', $post),
                    'url' => route('client.blog.show', $post),
                    'headline' => $post->title,
                    'description' => $post->excerpt,
                    'datePublished' => optional($post->published_at)->toIso8601String(),
                    'image' => $coverUrl,
                    'author' => [
                        '@type' => 'Organization',
                        'name' => $post->author->name ?? $siteName,
                    ],
                ],
            ];
        }

        $blogSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => 'Kiến thức & giải pháp tự động hóa - '.$siteName,
            'description' => 'Nơi cập nhật xu hướng công nghệ tự động hóa, giải pháp công nghiệp và kiến thức kỹ thuật do '.$siteName.' biên soạn.',
            'url' => $indexUrl,
            'inLanguage' => 'vi-VN',
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => config('app.url') ?? url('/'),
            ],
        ];

        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Kiến thức tự động hóa - '.$siteName,
            'description' => 'Danh sách bài viết mới nhất về thiết bị tự động hóa, giải pháp công nghiệp và câu chuyện thương hiệu '.$siteName.'.',
            'url' => $indexUrl,
            'inLanguage' => 'vi-VN',
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                'numberOfItems' => $latestPosts->count(),
                'itemListElement' => $itemListElements,
            ],
        ];

        return [
            $breadcrumb,
            $blogSchema,
            $collectionSchema,
        ];
    }

    protected function resolveIndexMeta(?Category $category, ?Tag $tag, ?string $searchTerm): array
    {
        $siteName = config('app.name');
        $title = 'Blog chia sẻ kiến thức kỹ thuật tự động hóa | '.$siteName;
        $description = 'Khám phá giải pháp tự động hóa, gợi ý ứng dụng công nghiệp và câu chuyện thương hiệu '.$siteName.'.';
        $heading = 'Blog chia sẻ kiến thức kỹ thuật tự động hóa từ '.$siteName;
        $subheading = 'Chia sẻ kiến thức về thiết bị tự động hóa, gợi ý giải pháp công nghiệp và câu chuyện thương hiệu '.$siteName.'.';
        $contextLabel = null;

        if ($category) {
            $title = $category->name.' | Blog chia sẻ kiến thức kỹ thuật tự động hóa '.$siteName;
            $description = 'Tin tức và cảm hứng xoay quanh '.$category->name.' – cập nhật bởi '.$siteName.'.';
            $heading = 'Chuyên mục: '.$category->name;
            $subheading = 'Những bài viết liên quan đến '.$category->name.' được cập nhật thường xuyên.';
            $contextLabel = 'Danh mục: '.$category->name;
        } elseif ($tag) {
            $title = 'Chủ đề #'.$tag->name.' | '.$siteName;
            $description = 'Tổng hợp bài viết nổi bật với hashtag #'.$tag->name.' tại '.$siteName.'.';
            $heading = 'Chủ đề #'.$tag->name;
            $subheading = 'Các bài viết xoay quanh chủ đề #'.$tag->name.' mà bạn quan tâm.';
            $contextLabel = '#'.$tag->name;
        } elseif ($searchTerm) {
            $title = 'Kết quả "'.$searchTerm.'" | '.$siteName;
            $description = 'Các bài viết liên quan tới "'.$searchTerm.'" do '.$siteName.' biên soạn.';
            $heading = 'Kết quả cho "'.$searchTerm.'"';
            $subheading = 'Những bài viết phù hợp nhất với từ khóa bạn đang tìm.';
            $contextLabel = 'Từ khóa: '.$searchTerm;
        }

        $keywords = implode(', ', array_filter([
            'blog chia sẻ kiến thức kỹ thuật tự động hóa',
            'kiến thức về thiết bị tự động hóa',
            'gợi ý giải pháp công nghiệp',
            'câu chuyện thương hiệu '.$siteName,
            $category?->name,
            $tag?->name,
            $searchTerm,
            $siteName,
        ]));

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'heading' => $heading,
            'subheading' => $subheading,
            'contextLabel' => $contextLabel,
        ];
    }

    protected function resolvePostMeta(Post $post): array
    {
        $siteName = config('app.name');
        $title = $post->meta_title ?? ($post->title.' | '.$siteName);
        $description = $post->meta_description ?? $post->excerpt;
        $keywords = $post->meta_keywords;
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $keywords = $keywords ?: $post->tags()->pluck('name')->implode(', ');
        $settings = View::shared('settings');
        $siteUrl = rtrim($settings->site_url ?? config('app.url') ?? url('/'), '/');
        if ($post->meta_canonical) {
            $canonical = $siteUrl.'/'.ltrim($post->meta_canonical, '/');
        } else {
            $canonical = $siteUrl.'/tu-dong-hoa/'.$post->slug;
        }
        $coverPath = $post->coverImagePath();
        $cover = $coverPath ? asset($coverPath) : asset('clients/assets/img/posts/no-image.webp');

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $canonical,
            'cover' => $cover,
        ];
    }

    protected function buildBreadcrumbSchema(array $items): array
    {
        $elements = [];
        foreach ($items as $index => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    protected function buildShowSchemas(Post $post, Collection $tags): array
    {
        $settings = \Illuminate\Support\Facades\View::shared('settings');
        $siteUrl = rtrim($settings->site_url ?? config('app.url') ?? url('/'), '/');
        $siteName = $settings->site_name ?? config('app.name') ?? 'AutoSensor Việt Nam';
        $canonicalUrl = $post->meta_canonical
            ? $siteUrl.'/'.ltrim($post->meta_canonical, '/')
            : $siteUrl.'/tu-dong-hoa/'.$post->slug;
        $postUrl = route('client.blog.show', $post);
        $blogIndexUrl = route('client.blog.index');

        // Lấy ảnh cover
        $coverPath = $post->coverImagePath();
        $coverUrl = $coverPath ? asset($coverPath) : asset('clients/assets/img/posts/no-image.webp');

        // Lấy thông tin author
        $authorName = $post->author?->name ?? $post->creator?->name ?? 'Đội ngũ biên tập';
        // Loại bỏ emoji và ký tự đặc biệt từ author name (cải thiện regex)
        $authorName = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $authorName);
        $authorName = preg_replace('/[^\p{L}\p{N}\s]/u', '', $authorName); // Loại bỏ tất cả ký tự đặc biệt
        $authorName = trim($authorName);
        if (empty($authorName)) {
            $authorName = 'Đội ngũ biên tập';
        }
        $authorSlug = Str::slug($authorName);
        $authorId = $siteUrl.'/tac-gia/'.$authorSlug;

        // Tính word count và time required
        $content = strip_tags($post->content ?? '');
        $wordCount = str_word_count($content);
        // Ước tính thời gian đọc: 200 từ/phút
        $readingTimeMinutes = max(1, ceil($wordCount / 200));
        $timeRequired = 'PT'.$readingTimeMinutes.'M';

        // Tối ưu: Cache getimagesize() để tránh file system access nhiều lần
        // Lấy logo organization và kích thước thực tế
        $logoUrl = asset('favicon-512x512.png');
        $logoWidth = 512;
        $logoHeight = 512;

        // Cache logo dimensions
        $logoCacheKey = 'blog_logo_dimensions_'.md5($logoUrl);
        $logoDimensions = Cache::remember($logoCacheKey, now()->addDays(30), function () use ($settings) {
            $defaultWidth = 512;
            $defaultHeight = 512;
            $defaultUrl = asset('favicon-512x512.png');
            
            if (file_exists(public_path('favicon-512x512.png'))) {
                $logoInfo = @getimagesize(public_path('favicon-512x512.png'));
                if ($logoInfo) {
                    return [
                        'url' => asset('favicon-512x512.png'),
                        'width' => $logoInfo[0],
                        'height' => $logoInfo[1],
                    ];
                }
            } elseif (isset($settings->site_logo) && ! empty($settings->site_logo)) {
                $logoPath = public_path('clients/assets/img/business/'.$settings->site_logo);
                if (file_exists($logoPath)) {
                    $logoInfo = @getimagesize($logoPath);
                    if ($logoInfo) {
                        return [
                            'url' => asset('clients/assets/img/business/'.$settings->site_logo),
                            'width' => $logoInfo[0],
                            'height' => $logoInfo[1],
                        ];
                    }
                }
            }
            
            return [
                'url' => $defaultUrl,
                'width' => $defaultWidth,
                'height' => $defaultHeight,
            ];
        });
        
        $logoUrl = $logoDimensions['url'];
        $logoWidth = $logoDimensions['width'];
        $logoHeight = $logoDimensions['height'];

        // Cache cover image dimensions
        $imageWidth = 1200;
        $imageHeight = 675;
        if ($coverPath) {
            $imageCacheKey = 'blog_image_dimensions_'.md5($coverPath);
            $imageDimensions = Cache::remember($imageCacheKey, now()->addDays(30), function () use ($coverPath) {
                $defaultWidth = 1200;
                $defaultHeight = 675;
                
                if (file_exists(public_path($coverPath))) {
                    $imageInfo = @getimagesize(public_path($coverPath));
                    if ($imageInfo && $imageInfo[0] >= 1200 && $imageInfo[1] >= 630) {
                        // Dùng kích thước thực tế nếu đủ lớn (>= 1200x630)
                        return [
                            'width' => $imageInfo[0],
                            'height' => $imageInfo[1],
                        ];
                    }
                }
                
                // Nếu ảnh nhỏ hơn, giữ nguyên 1200x675 (chuẩn Google Discover)
                return [
                    'width' => $defaultWidth,
                    'height' => $defaultHeight,
                ];
            });
            
            $imageWidth = $imageDimensions['width'];
            $imageHeight = $imageDimensions['height'];
        }

        $schemas = [];

        // 1. BreadcrumbList (giữ nguyên)
        $schemas[] = $this->buildBreadcrumbSchema([
            [
                'name' => 'Trang chủ',
                'url' => route('client.home.index'),
            ],
            [
                'name' => 'Công nghệ & Tự động hóa',
                'url' => $blogIndexUrl,
            ],
            [
                'name' => $post->title,
                'url' => $postUrl,
            ],
        ]);

        // 2. Organization (Publisher - dùng chung toàn site)
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => $siteName,
            'url' => $siteUrl,
            'logo' => $logoUrl,
        ];

        // 3. WebSite (với tên thương hiệu)
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $siteUrl.'/#website',
            'name' => $siteName,
            'url' => $siteUrl,
            'inLanguage' => 'vi-VN',
            'publisher' => [
                '@type' => 'Organization',
                '@id' => $siteUrl.'/#organization',
            ],
        ];

        // 4. WebPage
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $canonicalUrl,
            'url' => $canonicalUrl,
            'name' => $post->title,
            'inLanguage' => 'vi-VN',
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => $siteUrl.'/#website',
            ],
        ];

        // 5. BlogPosting (quan trọng nhất - đầy đủ)
        // Đảm bảo description không rỗng
        $description = $post->meta_description ?? $post->excerpt_text ?? $post->excerpt ?? '';
        if (empty(trim($description))) {
            // Fallback: tạo description từ title và excerpt
            $description = mb_substr(strip_tags($post->content ?? ''), 0, 160);
            if (empty($description)) {
                $description = $post->title.' - '.$siteName;
            }
        }

        // Đảm bảo dates luôn có giá trị hợp lệ
        $datePublished = ($post->published_at ?? $post->created_at);
        if (! $datePublished) {
            $datePublished = now();
        }
        $dateModified = $post->updated_at ?? $datePublished;

        // Đảm bảo image dimensions là số nguyên dương
        $imageWidth = max(1, (int) $imageWidth);
        $imageHeight = max(1, (int) $imageHeight);
        $logoWidth = max(1, (int) $logoWidth);
        $logoHeight = max(1, (int) $logoHeight);

        $blogPostingSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $canonicalUrl.'#blogposting',
            'headline' => $post->title,
            'description' => $description,
            'inLanguage' => 'vi-VN',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl,
            ],
            'image' => [
                '@type' => 'ImageObject',
                'url' => $coverUrl,
                'width' => $imageWidth,
                'height' => $imageHeight,
            ],
            'author' => [
                '@type' => 'Person',
                '@id' => $authorId,
                'url' => Setting::getValue('facebook_link') ?? 'https://www.facebook.com/ducnobi2004',
                'name' => $authorName,
            ],
            'publisher' => [
                '@type' => 'Organization',
                '@id' => $siteUrl.'/#organization',
                'name' => $siteName,
                'url' => $siteUrl,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                    'width' => $logoWidth,
                    'height' => $logoHeight,
                ],
            ],
            'datePublished' => $datePublished->toIso8601String(),
            'dateModified' => $dateModified->toIso8601String(),
            'wordCount' => max(0, (int) $wordCount),
            'timeRequired' => $timeRequired,
            'isPartOf' => [
                '@type' => 'Blog',
                '@id' => $blogIndexUrl,
            ],
        ];

        // Thêm articleSection nếu có category
        if ($post->category) {
            $blogPostingSchema['articleSection'] = $post->category->name;
        } else {
            $blogPostingSchema['articleSection'] = 'Công nghệ & Tự động hóa';
        }

        // Thêm keywords - ưu tiên meta_keywords, fallback về tags
        $keywords = null;
        if (! empty($post->meta_keywords)) {
            $keywords = $post->meta_keywords;
        } elseif ($tags->isNotEmpty()) {
            $keywords = $tags->pluck('name')->implode(', ');
        }

        if ($keywords) {
            $blogPostingSchema['keywords'] = $keywords;
        }

        $schemas[] = $blogPostingSchema;

        return $schemas;
    }

    protected function buildContentAnchors(?string $content): array
    {
        $content = $content ?? '';

        if (trim($content) === '') {
            return [
                'content' => '',
                'toc' => collect(),
            ];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $tocItems = [];
        $usedIds = [];

        // Lấy tất cả các heading (h2, h3) theo thứ tự xuất hiện trong DOM
        $xpath = new \DOMXPath($dom);
        $headings = $xpath->query('//h2 | //h3');

        foreach ($headings as $node) {
            // Đảm bảo node là DOMElement để có thể setAttribute
            if (! ($node instanceof \DOMElement)) {
                continue;
            }

            $tag = $node->nodeName; // 'h2' hoặc 'h3'
            $text = trim($node->textContent ?? '');

            if ($text === '') {
                continue;
            }

            // Tạo ID unique từ text
            $baseId = Str::slug(Str::limit($text, 80, ''));
            if ($baseId === '') {
                $baseId = 'section-'.(count($tocItems) + 1);
            }

            // Đảm bảo ID không trùng
            $id = $baseId;
            $suffix = 1;
            while (in_array($id, $usedIds, true)) {
                $id = $baseId.'-'.$suffix;
                $suffix++;
            }

            $usedIds[] = $id;
            $node->setAttribute('id', $id);

            // Lưu theo thứ tự xuất hiện trong content
            $tocItems[] = [
                'id' => $id,
                'label' => $text,
                'tag' => $tag,
            ];
        }

        return [
            'content' => $dom->saveHTML() ?: $content,
            'toc' => collect($tocItems),
        ];
    }

    protected function resolveTags(Post $post): Collection
    {
        $allTagIds = collect();

        // Lấy tags từ relationship (bảng tags với entity_id và entity_type)
        $tagsFromRelationship = Tag::query()
            ->where('entity_id', $post->id)
            ->where('entity_type', Post::class)
            ->where('is_active', true)
            ->get();

        if ($tagsFromRelationship->isNotEmpty()) {
            $allTagIds = $allTagIds->merge($tagsFromRelationship->pluck('id'));
        }

        // Lấy tags từ tag_ids (JSON column) nếu có
        $tagIdsFromColumn = collect($post->tag_ids ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($tagIdsFromColumn->isNotEmpty()) {
            $allTagIds = $allTagIds->merge($tagIdsFromColumn);
        }

        // Loại bỏ trùng lặp và lấy tags
        $uniqueTagIds = $allTagIds->unique()->values();

        if ($uniqueTagIds->isEmpty()) {
            return collect();
        }

        return Tag::query()
            ->whereIn('id', $uniqueTagIds)
            ->where('entity_type', Post::class)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
