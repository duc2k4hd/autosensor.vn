<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--path= : Custom backup path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database to SQL file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = DB::connection();
        $config = $connection->getConfig();

        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'] ?? '';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;

        // Check if mysqldump is available
        exec('mysqldump --version 2>&1', $versionOutput, $versionCode);
        if ($versionCode !== 0) {
            $this->error('mysqldump không được tìm thấy. Vui lòng cài đặt MySQL client tools.');
            $this->error('Trên Windows, thêm MySQL bin directory vào PATH hoặc sử dụng đường dẫn đầy đủ.');

            return Command::FAILURE;
        }

        $backupDir = $this->option('path') ?? storage_path('app/backups');
        if (! is_dir($backupDir)) {
            if (! mkdir($backupDir, 0755, true)) {
                $this->error("Không thể tạo thư mục backup: {$backupDir}");

                return Command::FAILURE;
            }
        }

        $fileName = 'backup_'.$database.'_'.date('Y-m-d_His').'.sql';
        $filePath = $backupDir.'/'.$fileName;

        $this->info('Đang backup database...');

        // Build mysqldump command
        // On Windows, use cmd /c for proper command execution
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            // Windows: Use environment variable for password (more secure)
            $envVars = [];
            if ($password) {
                $envVars['MYSQL_PWD'] = $password;
            }

            $command = sprintf(
                'mysqldump -h%s -P%s -u%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            // Execute with environment variables
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptorspec, $pipes, null, $envVars);

            if (! is_resource($process)) {
                $this->error('Không thể khởi tạo process backup.');

                return Command::FAILURE;
            }

            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);
            $output = array_filter(explode("\n", $output.$errors));
        } else {
            // Linux/Unix: Use --password= option
            $passwordPart = $password ? '--password='.escapeshellarg($password) : '';
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s %s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $passwordPart,
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            exec($command, $output, $returnCode);
        }

        // Check if file was created and has content
        if (! file_exists($filePath)) {
            $this->error('File backup không được tạo.');
            if (! empty($output)) {
                $this->error('Lỗi: '.implode("\n", $output));
            }

            return Command::FAILURE;
        }

        $fileSize = filesize($filePath);

        if ($fileSize === 0) {
            $this->error('File backup rỗng (0KB).');
            if (! empty($output)) {
                $this->error('Lỗi: '.implode("\n", $output));
            }
            unlink($filePath); // Delete empty file

            return Command::FAILURE;
        }

        if ($returnCode !== 0) {
            $this->error('Lỗi khi backup database (return code: '.$returnCode.')');
            if (! empty($output)) {
                $this->error('Chi tiết lỗi: '.implode("\n", $output));
            }
            if (file_exists($filePath) && filesize($filePath) === 0) {
                unlink($filePath);
            }

            return Command::FAILURE;
        }

        $this->info("Backup thành công: {$fileName}");
        $this->info('Kích thước: '.$this->formatBytes($fileSize));
        $this->info("Đường dẫn: {$filePath}");

        // Keep only last 10 backups
        $this->cleanupOldBackups($backupDir);

        return Command::SUCCESS;
    }

    protected function cleanupOldBackups(string $backupDir): void
    {
        $files = glob($backupDir.'/backup_*.sql');
        if (count($files) > 10) {
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            $filesToDelete = array_slice($files, 0, count($files) - 10);
            foreach ($filesToDelete as $file) {
                unlink($file);
                $this->info('Đã xóa backup cũ: '.basename($file));
            }
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
