<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SitemapService;
use Illuminate\Support\Facades\Cache;

class SitemapPublicController extends Controller
{
    public function __construct(protected SitemapService $sitemapService) {}

    public function landing()
    {
        $siteName = Setting::getValue('site_name', config('app.name'));
        $siteUrl = config('app.url');
        $sitemapUrl = route('client.sitemap.landing');
        $sitemapXmlUrl = url('/sitemap.xml');

        $metaTitle = 'Sitemap - '.$siteName;
        $metaDescription = 'Sitemap của '.$siteName.' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website.';
        $metaKeywords = 'sitemap, '.$siteName.', bản đồ trang web, tìm kiếm nội dung';

        $sitemaps = [
            ['name' => 'Sitemap Index', 'description' => 'Danh sách toàn bộ sitemap con.', 'url' => $sitemapXmlUrl, 'icon' => '🗂️'],
            ['name' => 'Bài viết', 'description' => 'Danh sách bài viết.', 'url' => url('/sitemap-posts.xml'), 'icon' => '📝'],
            ['name' => 'Sản phẩm', 'description' => 'Danh sách sản phẩm.', 'url' => url('/sitemap-products.xml'), 'icon' => '🛒'],
            ['name' => 'Danh mục', 'description' => 'Danh sách danh mục.', 'url' => url('/sitemap-categories.xml'), 'icon' => '📂'],
            ['name' => 'Tags - Sản phẩm', 'description' => 'Danh sách thẻ sản phẩm.', 'url' => url('/sitemap-tags-products.xml'), 'icon' => '🏷️'],
            ['name' => 'Tags - Bài viết', 'description' => 'Danh sách thẻ bài viết.', 'url' => url('/sitemap-tags-posts.xml'), 'icon' => '🏷️'],
            ['name' => 'Trang tĩnh', 'description' => 'Các trang tĩnh.', 'url' => url('/sitemap-pages.xml'), 'icon' => '📄'],
            ['name' => 'Hình ảnh', 'description' => 'Các ảnh quan trọng.', 'url' => url('/sitemap-images.xml'), 'icon' => '🖼️'],
        ];

        return view('clients.pages.sitemap.index', compact(
            'sitemaps', 'siteName', 'siteUrl', 'sitemapUrl', 'sitemapXmlUrl',
            'metaTitle', 'metaDescription', 'metaKeywords'
        ));
    }

    // ===============================
    //      SITEMAP INDEX (CACHE)
    // ===============================
    public function index()
    {
        $cacheKey = 'sitemap.index';

        return $this->xmlResponse(
            Cache::remember($cacheKey, 1440, function () {
                return $this->safeGenerate(fn () => $this->sitemapService->generateIndex());
            })
        );
    }

    // ===============================
    //          POSTS
    // ===============================
    public function posts(int $page = 1)
    {
        $page = max($page, 1);
        $cacheKey = "sitemap.posts.$page";

        return $this->xmlResponse(
            Cache::remember($cacheKey, 1440, function () use ($page) {
                return $this->safeGenerate(fn () => $this->sitemapService->generatePosts($page));
            })
        );
    }

    // ===============================
    //          PRODUCTS
    // ===============================
    public function products(int $page = 1)
    {
        $page = max($page, 1);
        $cacheKey = "sitemap.products.$page";

        return $this->xmlResponse(
            Cache::remember($cacheKey, 1440, function () use ($page) {
                return $this->safeGenerate(fn () => $this->sitemapService->generateProducts($page));
            })
        );
    }

    // ===============================
    //         CATEGORIES
    // ===============================
    public function categories()
    {
        return $this->xmlResponse(
            Cache::remember('sitemap.categories', 1440, function () {
                return $this->safeGenerate(fn () => $this->sitemapService->generateCategories());
            })
        );
    }

    // ===============================
    //       TAGS - PRODUCTS
    // ===============================
    public function tagsProducts()
    {
        return $this->xmlResponse(
            Cache::remember('sitemap.tags_products', 1440, function () {
                return $this->safeGenerate(fn () => $this->sitemapService->generateTagsProducts());
            })
        );
    }

    // ===============================
    //        TAGS - POSTS
    // ===============================
    public function tagsPosts()
    {
        return $this->xmlResponse(
            Cache::remember('sitemap.tags_posts', 1440, function () {
                return $this->safeGenerate(fn () => $this->sitemapService->generateTagsPosts());
            })
        );
    }

    // ===============================
    //            PAGES
    // ===============================
    public function pages()
    {
        return $this->xmlResponse(
            Cache::remember('sitemap.pages', 1440, function () {
                return $this->safeGenerate(fn () => $this->sitemapService->generatePages());
            })
        );
    }

    // ===============================
    //           IMAGES
    // ===============================
    public function images()
    {
        return $this->xmlResponse(
            Cache::remember('sitemap.images', 1440, function () {
                return $this->safeGenerate(fn () => $this->sitemapService->generateImages());
            })
        );
    }

    // ===============================
    //       XML SAFE RESPONSE
    // ===============================
    protected function xmlResponse(?string $content)
    {
        if (! $content) {
            $content = '<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>';
        }

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    // ===============================
    //   SAFE GENERATE (NO 5XX ERROR)
    // ===============================
    protected function safeGenerate(\Closure $callback): string
    {
        try {
            return $callback() ?: '<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>';
        } catch (\Throwable $e) {

            // Log lỗi thật vào storage/logs
            \Log::error('Sitemap Error: '.$e->getMessage());

            // Không trả 500 cho Google → trả XML rỗng hợp lệ
            return '<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>';
        }
    }
}
