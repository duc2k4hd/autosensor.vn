<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {file : Path to SQL backup file} {--force : Force restore without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database from SQL backup file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File backup không tồn tại: {$filePath}");

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Bạn có chắc chắn muốn restore database? Dữ liệu hiện tại sẽ bị ghi đè!')) {
                $this->info('Đã hủy restore.');

                return Command::SUCCESS;
            }
        }

        $connection = DB::connection();
        $config = $connection->getConfig();

        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $host = $config['host'];
        $port = $config['port'] ?? 3306;

        $this->info('Đang restore database...');

        // Build mysql command
        $command = sprintf(
            'mysql -h%s -P%s -u%s -p%s %s < %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Lỗi khi restore database. Kiểm tra file backup và quyền truy cập.');

            return Command::FAILURE;
        }

        $this->info('Restore database thành công!');

        return Command::SUCCESS;
    }
}
