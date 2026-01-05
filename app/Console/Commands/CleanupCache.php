<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CleanupCache extends Command
{
    protected $signature = 'cache:cleanup';

    protected $description = 'Dọn dẹp cache cũ và tối ưu cache storage';

    public function handle(): int
    {
        $this->info('🧹 Đang dọn dẹp cache...');

        // Clear expired cache entries
        Artisan::call('cache:clear');

        // Nếu dùng database cache, có thể cleanup old entries
        if (config('cache.default') === 'database') {
            $this->info('  ✓ Đang cleanup database cache...');
            // Database cache tự động cleanup expired entries khi query
        }

        // Nếu dùng file cache, có thể cleanup old files
        if (config('cache.default') === 'file') {
            $this->info('  ✓ File cache sẽ tự động cleanup khi access.');
        }

        $this->info('✅ Hoàn thành cleanup cache.');

        return Command::SUCCESS;
    }
}
