<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearActivityLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa sạch bảng activity_logs theo lô để giải phóng dung lượng Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Bắt đầu quá trình dọn dẹp Activity Logs...');

        // Kiểm tra xem bảng có dữ liệu không
        $totalCount = \Illuminate\Support\Facades\DB::table('activity_logs')->count();
        $this->info("📊 Tổng số bản ghi log cần xử lý: " . number_format($totalCount));

        if ($totalCount === 0) {
            $this->info('✨ Bảng log đã trống. Không cần xử lý.');
            return 0;
        }

        if (!$this->confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ Activity Logs không? Hành động này không thể hoàn tác!')) {
            $this->warn('Hủy bỏ lệnh.');
            return 0;
        }

        $batchSize = 50000;
        $deletedTotal = 0;
        $bar = $this->output->createProgressBar($totalCount);

        // Tắt Foreign Key Checks
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            while (true) {
                // Xóa trực tiếp qua Query Builder để nhanh nhất
                $deleted = \Illuminate\Support\Facades\DB::table('activity_logs')->limit($batchSize)->delete();

                if ($deleted === 0) {
                    break;
                }

                $deletedTotal += $deleted;
                $bar->advance($deleted);
                
                if ($deletedTotal % 500000 === 0) {
                    $this->info("\n✅ Đã xóa " . number_format($deletedTotal) . " logs...");
                }
            }
        } catch (\Exception $e) {
            $this->error("\n❌ Lỗi trong quá trình xóa: " . $e->getMessage());
        } finally {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $bar->finish();
        $this->info("\n\n✨ Hoàn tất! Đã dọn dẹp " . number_format($deletedTotal) . " bản ghi Activity Logs.");

        return 0;
    }
}
