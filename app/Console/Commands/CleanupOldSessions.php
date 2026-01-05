<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldSessions extends Command
{
    protected $signature = 'sessions:cleanup {--days=30 : Số ngày để giữ lại sessions}';

    protected $description = 'Xóa sessions cũ hơn N ngày';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("🧹 Đang xóa sessions cũ hơn {$days} ngày (trước {$cutoffDate->format('d/m/Y H:i')})...");

        $deleted = DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->delete();

        $this->info("✅ Đã xóa {$deleted} sessions cũ.");

        return Command::SUCCESS;
    }
}
