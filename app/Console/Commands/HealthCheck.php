<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheck extends Command
{
    protected $signature = 'app:health-check';

    protected $description = 'Check application health (database, cache, storage)';

    public function handle(): int
    {
        $this->info('🏥 Running health checks...');
        $this->newLine();

        $checks = [
            'Database Connection' => $this->checkDatabase(),
            'Cache System' => $this->checkCache(),
            'Storage Writable' => $this->checkStorage(),
            'Queue Connection' => $this->checkQueue(),
        ];

        $allPassed = true;
        foreach ($checks as $check => $result) {
            if ($result) {
                $this->info("✅ {$check}: OK");
            } else {
                $this->error("❌ {$check}: FAILED");
                $allPassed = false;
            }
        }

        $this->newLine();
        if ($allPassed) {
            $this->info('✅ All health checks passed!');

            return Command::SUCCESS;
        } else {
            $this->error('❌ Some health checks failed!');

            return Command::FAILURE;
        }
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health_check_'.time();
            Cache::put($key, 'test', 10);
            $result = Cache::get($key) === 'test';
            Cache::forget($key);

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            return Storage::disk('local')->put('health_check.txt', 'test') &&
                   Storage::disk('local')->delete('health_check.txt');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            $connection = config('queue.default');

            return $connection !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
