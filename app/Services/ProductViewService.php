<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ProductViewService
{
    /**
     * Record a product view
     */
    public function recordView(Product $product): void
    {
        $accountId = Auth::id();
        $sessionId = Session::getId();

        // Chỉ lưu nếu chưa xem trong 30 phút gần đây (tránh spam)
        $recentView = ProductView::where('product_id', $product->id)
            ->forUser($accountId, $sessionId)
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->first();

        if ($recentView) {
            return;
        }

        // Truncate user_agent to max 500 characters to prevent database errors
        $userAgent = request()->userAgent();
        if ($userAgent && strlen($userAgent) > 500) {
            $userAgent = substr($userAgent, 0, 500);
        }

        ProductView::create([
            'product_id' => $product->id,
            'account_id' => $accountId,
            'session_id' => $accountId ? null : $sessionId,
            'ip' => request()->ip(),
            'user_agent' => $userAgent,
            'viewed_at' => now(),
        ]);

        // Giữ tối đa 50 bản ghi gần nhất cho mỗi user
        $this->cleanupOldViews($accountId, $sessionId);
    }

    /**
     * Get recently viewed products for current user
     */
    public function getRecentProducts(int $limit = 10)
    {
        $accountId = Auth::id();
        $sessionId = Session::getId();

        return ProductView::getRecentForUser($accountId, $sessionId, $limit);
    }

    /**
     * Cleanup old views (keep only last 50 per user)
     */
    protected function cleanupOldViews(?int $accountId, ?string $sessionId): void
    {
        // Lấy ID của 50 bản ghi mới nhất để giữ lại
        $keepIds = ProductView::forUser($accountId, $sessionId)
            ->orderByDesc('viewed_at')
            ->limit(50)
            ->pluck('id');

        // Xóa tất cả các bản ghi khác (nếu có)
        if ($keepIds->isNotEmpty()) {
            ProductView::forUser($accountId, $sessionId)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }
}
