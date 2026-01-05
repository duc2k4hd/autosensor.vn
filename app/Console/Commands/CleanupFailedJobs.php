<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupFailedJobs extends Command
{
    protected $signature = 'queue:cleanup-failed {--days=7 : Số ngày để giữ lại failed jobs}';

    protected $description = 'Xóa failed jobs cũ hơn N ngày';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("🧹 Đang xóa failed jobs cũ hơn {$days} ngày...");

        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();

        $this->info("✅ Đã xóa {$deleted} failed jobs cũ.");

        return Command::SUCCESS;
    }
}
