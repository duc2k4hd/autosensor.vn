<?php

namespace App\Console\Commands;

use App\Services\PostImportExportService;
use Illuminate\Console\Command;

class ImportPostsExcel extends Command
{
    protected $signature = 'posts:import {path : Input xlsx file path}';

    protected $description = 'Import posts from Excel file (xlsx)';

    public function handle(PostImportExportService $service)
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error('File not found: '.$path);
            return 1;
        }

        $this->info('Starting import...');
        $report = $service->importFromFile($path);

        $this->info('Import completed.');
        $this->line("Processed: {$report['processed']}");
        $this->line("Created: {$report['created']}");
        $this->line("Updated: {$report['updated']}");
        $this->line("Skipped: {$report['skipped']}");

        if (! empty($report['errors'])) {
            $this->error('Errors:');
            foreach ($report['errors'] as $err) {
                $this->line(' - '.$err);
            }
        }

        return 0;
    }
}
