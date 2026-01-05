<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeApplication extends Command
{
    protected $signature = 'app:optimize';

    protected $description = 'Optimize application for production (clear and cache all)';

    public function handle(): int
    {
        $this->info('🚀 Optimizing application for production...');

        // Clear all caches
        $this->info('Clearing old caches...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('event:clear');

        // Cache everything
        $this->info('Caching configuration...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');

        // Optimize autoloader
        $this->info('Optimizing autoloader...');
        exec('composer dump-autoload --optimize --no-dev', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->warn('Could not optimize autoloader. Make sure composer is available.');
        }

        $this->info('✅ Application optimized successfully!');

        return Command::SUCCESS;
    }
}
