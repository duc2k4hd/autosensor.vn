<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupLogs extends Command
{
    protected $signature = 'logs:cleanup {--keep=7 : Số lượng log files gần nhất cần giữ lại}';

    protected $description = 'Xóa log files cũ, chỉ giữ lại N log files gần nhất';

    public function handle(): int
    {
        $keep = (int) $this->option('keep');
        $logPath = storage_path('logs');

        if (! is_dir($logPath)) {
            $this->error("❌ Thư mục logs không tồn tại: {$logPath}");

            return Command::FAILURE;
        }

        $this->info("🧹 Đang dọn dẹp log files (giữ lại {$keep} files gần nhất)...");

        // Lấy tất cả log files
        $logFiles = glob($logPath.'/*.log');

        if (empty($logFiles)) {
            $this->info('✅ Không có log files nào để dọn dẹp.');

            return Command::SUCCESS;
        }

        // Sắp xếp theo thời gian modified (mới nhất trước)
        usort($logFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $totalFiles = count($logFiles);
        $filesToKeep = array_slice($logFiles, 0, $keep);
        $filesToDelete = array_slice($logFiles, $keep);

        if (empty($filesToDelete)) {
            $this->info("✅ Tất cả {$totalFiles} log files đều được giữ lại (≤ {$keep} files).");

            return Command::SUCCESS;
        }

        $deletedCount = 0;
        $deletedSize = 0;

        foreach ($filesToDelete as $file) {
            $fileSize = filesize($file);
            $fileName = basename($file);

            if (File::delete($file)) {
                $deletedCount++;
                $deletedSize += $fileSize;
                $this->line("  ✓ Đã xóa: {$fileName} (".$this->formatBytes($fileSize).')');
            } else {
                $this->warn("  ⚠ Không thể xóa: {$fileName}");
            }
        }

        $this->newLine();
        $this->info('✅ Hoàn thành!');
        $this->info("   - Tổng số log files: {$totalFiles}");
        $this->info('   - Đã giữ lại: '.count($filesToKeep).' files');
        $this->info("   - Đã xóa: {$deletedCount} files");
        $this->info('   - Dung lượng đã giải phóng: '.$this->formatBytes($deletedSize));

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
