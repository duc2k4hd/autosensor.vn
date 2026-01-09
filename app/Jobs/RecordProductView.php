<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductView;
use App\Services\ProductViewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RecordProductView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $productId;
    public ?int $accountId;
    public ?string $sessionId;
    public ?string $ip;
    public ?string $userAgent;

    /**
     * Create a new job instance.
     */
    public function __construct(int $productId)
    {
        $this->productId = $productId;
        $this->accountId = Auth::id();
        $this->sessionId = Session::getId();
        $this->ip = request()->ip();
        
        // Truncate user_agent to max 500 characters
        $userAgent = request()->userAgent();
        $this->userAgent = $userAgent && strlen($userAgent) > 500 ? substr($userAgent, 0, 500) : $userAgent;
    }

    /**
     * Execute the job.
     */
    public function handle(ProductViewService $productViewService): void
    {
        $product = Product::find($this->productId);
        
        if (!$product) {
            return;
        }

        // Chỉ lưu nếu chưa xem trong 30 phút gần đây (tránh spam)
        $recentView = ProductView::where('product_id', $this->productId)
            ->forUser($this->accountId, $this->sessionId)
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->first();

        if ($recentView) {
            return;
        }

        ProductView::create([
            'product_id' => $this->productId,
            'account_id' => $this->accountId,
            'session_id' => $this->accountId ? null : $this->sessionId,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'viewed_at' => now(),
        ]);

        // Giữ tối đa 50 bản ghi gần nhất cho mỗi user
        $this->cleanupOldViews();
    }

    /**
     * Cleanup old views (keep only last 50 per user)
     */
    protected function cleanupOldViews(): void
    {
        // Lấy ID của 50 bản ghi mới nhất để giữ lại
        $keepIds = ProductView::forUser($this->accountId, $this->sessionId)
            ->orderByDesc('viewed_at')
            ->limit(50)
            ->pluck('id');

        // Xóa tất cả các bản ghi khác (nếu có)
        if ($keepIds->isNotEmpty()) {
            ProductView::forUser($this->accountId, $this->sessionId)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }
}
