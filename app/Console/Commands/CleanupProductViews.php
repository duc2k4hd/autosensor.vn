<?php

namespace App\Console\Commands;

use App\Models\ProductView;
use Illuminate\Console\Command;

class CleanupProductViews extends Command
{
    protected $signature = 'product-views:cleanup {--days=90 : Số ngày để giữ lại product views}';

    protected $description = 'Xóa product views cũ hơn N ngày để tối ưu database';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("🧹 Đang xóa product views cũ hơn {$days} ngày...");

        $deleted = ProductView::where('viewed_at', '<', $cutoffDate)
            ->orWhere('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("✅ Đã xóa {$deleted} product views cũ.");

        return Command::SUCCESS;
    }
}
