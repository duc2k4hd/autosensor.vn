<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateClick
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra nếu có tham số ref trong URL
        if ($ref = $request->get('ref')) {
            // Tìm affiliate code
            $affiliate = Affiliate::where('code', $ref)
                ->where('status', 'active')
                ->first();

            if ($affiliate) {
                // Tăng số lượt click
                $affiliate->increment('clicks');

                // Lưu affiliate code vào cookie (30 ngày)
                Cookie::queue('affiliate_ref', $ref, 60 * 24 * 30);
            }
        }

        $response = $next($request);

        return $response;
    }
}
