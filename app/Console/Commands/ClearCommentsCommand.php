<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearCommentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comments:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa sạch 5 triệu bình luận theo lô để tránh lag Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Bắt đầu quá trình dọn dẹp 5 triệu bình luận...');

        $totalCount = \App\Models\Comment::count();
        $this->info('📊 Tổng số bản ghi cần xử lý: '.number_format($totalCount));

        if (! $this->confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ bình luận không? Hành động này không thể hoàn tác!')) {
            $this->warn('Hủy bỏ lệnh.');

            return 0;
        }

        $batchSize = 50000;
        $deletedTotal = 0;
        $bar = $this->output->createProgressBar($totalCount);

        // Tắt Foreign Key Checks để xóa cho nhanh (đặc biệt là self-reference parent_id)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            while (true) {
                // Sử dụng delete() với limit để tối ưu tốc độ và không khóa bảng quá lâu
                $deleted = \App\Models\Comment::limit($batchSize)->delete();

                if ($deleted === 0) {
                    break;
                }

                $deletedTotal += $deleted;
                $bar->advance($deleted);

                // Giải phóng bộ nhớ PHP nếu cần (mặc dù delete() trên Query Builder ít tốn RAM hơn Eloquent collection)
                if ($deletedTotal % 500000 === 0) {
                    $this->info("\n✅ Đã xóa ".number_format($deletedTotal).' bản ghi...');
                }
            }
        } catch (\Exception $e) {
            $this->error("\n❌ Lỗi trong quá trình xóa: ".$e->getMessage());
        } finally {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $bar->finish();
        $this->info("\n\n✨ Hoàn tất! Đã xóa tổng cộng ".number_format($deletedTotal).' bình luận.');

        // Dọn dẹp Cache liên quan
        $this->call('cache:clear');
        \Illuminate\Support\Facades\Cache::forget('admin_comment_stats');
        \Illuminate\Support\Facades\Cache::forget('admin_comment_stats_v2');

        $this->info('🧹 Đã dọn dẹp sạch sẽ Cache bình luận.');

        return 0;
    }
}
