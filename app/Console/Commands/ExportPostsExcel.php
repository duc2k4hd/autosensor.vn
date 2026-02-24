<?php

namespace App\Console\Commands;

use App\Services\PostImportExportService;
use Illuminate\Console\Command;

class ExportPostsExcel extends Command
{
    protected $signature = 'posts:export {--path= : Output file path (xlsx)} {--template : Export template only}';

    protected $description = 'Export posts to Excel (or export template)';

    public function handle(PostImportExportService $service)
    {
        $path = $this->option('path') ?: storage_path('app/posts_export.xlsx');

        if ($this->option('template')) {
            $service->exportTemplate($path);
            $this->info("Template exported to {$path}");
            return 0;
        }

        $this->info('Exporting posts...');
        $service->exportAll($path);
        $this->info("Export finished: {$path}");
        return 0;
    }
}
