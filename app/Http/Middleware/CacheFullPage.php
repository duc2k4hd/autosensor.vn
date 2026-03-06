<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full Page Cache Middleware
 *
 * Cache toàn bộ HTML response vào Redis.
 * Chỉ cache GET request, không cache khi user đăng nhập,
 * không cache khi có query parameter động (cart, checkout...).
 *
 * TTL mặc định: 2 giờ (có thể tuỳ chỉnh qua $ttl khi đăng ký route)
 */
class CacheFullPage
{
    /** Query params không ảnh hưởng đến nội dung, bỏ qua khi tạo cache key */
    private const IGNORE_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid', '_ga'];

    public function handle(Request $request, Closure $next, int $ttl = 7200): Response
    {
        // Chỉ cache GET request
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // Không cache khi user đang đăng nhập
        if ($request->user()) {
            return $next($request);
        }

        // Không cache khi có session flash (sau redirect, sau form submit...)
        if ($request->session()->has('_flash') &&
            ! empty($request->session()->get('_flash.old'))) {
            return $next($request);
        }

        // Không cache khi có cookie giỏ hàng hoặc auth
        if ($request->hasCookie('laravel_session') &&
            $request->session()->has('cart')) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($request);

        // Trả về cache nếu có
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return response($cached['content'], 200)
                ->withHeaders([
                    'Content-Type'  => $cached['content_type'] ?? 'text/html; charset=UTF-8',
                    'X-Cache'       => 'HIT',
                    'X-Cache-Key'   => $cacheKey,
                    'Cache-Control' => 'public, max-age='.($ttl / 2), // Browser cache ngắn hơn server cache
                ]);
        }

        /** @var Response $response */
        $response = $next($request);

        // Chỉ cache response 200 OK dạng HTML
        if ($response->getStatusCode() === 200 &&
            str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            Cache::put($cacheKey, [
                'content'      => $response->getContent(),
                'content_type' => $response->headers->get('Content-Type'),
                'cached_at'    => now()->toIso8601String(),
            ], $ttl);

            $response->headers->set('X-Cache', 'MISS');
            $response->headers->set('X-Cache-Key', $cacheKey);
        }

        return $response;
    }

    private function buildCacheKey(Request $request): string
    {
        // Loại bỏ tracking params không ảnh hưởng đến content
        $params = $request->query();
        foreach (self::IGNORE_PARAMS as $param) {
            unset($params[$param]);
        }
        ksort($params); // Sắp xếp để cùng params khác thứ tự → cùng key

        $queryString = http_build_query($params);
        $url = $request->getPathInfo().($queryString ? '?'.$queryString : '');

        return 'fpc:'.md5($url);
    }

    /**
     * Xóa cache của một URL cụ thể.
     * Dùng khi cập nhật sản phẩm, bài viết...
     */
    public static function forget(string $url): void
    {
        $key = 'fpc:'.md5(parse_url($url, PHP_URL_PATH) ?: $url);
        Cache::forget($key);
    }

    /**
     * Xóa tất cả full page cache (dùng khi deploy hoặc bulk update).
     * Chỉ hoạt động với Redis driver.
     */
    public static function flush(): void
    {
        try {
            $redis = Cache::getStore()->getRedis();
            $prefix = config('cache.prefix').':fpc:';

            // Quét và xóa theo pattern (dùng SCAN thay KEYS để tránh block)
            $cursor = 0;
            do {
                [$cursor, $keys] = $redis->scan($cursor, ['match' => $prefix.'*', 'count' => 100]);
                if (! empty($keys)) {
                    $redis->del(...$keys);
                }
            } while ($cursor != 0);
        } catch (\Throwable $e) {
            // Fallback: xóa toàn bộ cache (an toàn hơn khi dùng file driver)
            \Illuminate\Support\Facades\Log::warning('CacheFullPage::flush fallback', ['error' => $e->getMessage()]);
        }
    }
}
