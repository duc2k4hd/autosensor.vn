<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// SCHEDULED TASKS (CRON JOBS)
// ============================================

// Reset đơn hàng chưa thanh toán/chưa xác nhận sau 7 ngày và trả về số lượng sản phẩm
// Chạy hàng ngày lúc 2:00 AM
Schedule::command('orders:reset-unpaid --days=7')
    ->dailyAt('02:00')
    ->description('Reset đơn hàng chưa thanh toán sau 7 ngày')
    ->withoutOverlapping()
    ->runInBackground();

// Cleanup log files - chỉ giữ lại 7 log files gần nhất
// Chạy hàng ngày lúc 3:00 AM
Schedule::command('logs:cleanup --keep=7')
    ->dailyAt('03:00')
    ->description('Xóa log files cũ, chỉ giữ 7 files gần nhất')
    ->withoutOverlapping();

// Cleanup old sessions (giữ lại 30 ngày)
// Chạy hàng tuần vào Chủ Nhật lúc 4:00 AM
Schedule::command('sessions:cleanup --days=30')
    ->weeklyOn(0, '04:00')
    ->description('Xóa sessions cũ hơn 30 ngày')
    ->withoutOverlapping();

// Cleanup failed jobs (giữ lại 7 ngày)
// Chạy hàng ngày lúc 5:00 AM
Schedule::command('queue:cleanup-failed --days=7')
    ->dailyAt('05:00')
    ->description('Xóa failed jobs cũ hơn 7 ngày')
    ->withoutOverlapping();

// Cleanup product views (giữ lại 90 ngày)
// Chạy hàng tuần vào Thứ 2 lúc 6:00 AM
Schedule::command('product-views:cleanup --days=90')
    ->weeklyOn(1, '06:00')
    ->description('Xóa product views cũ hơn 90 ngày')
    ->withoutOverlapping();

// Cleanup cache
// Chạy hàng ngày lúc 1:00 AM
Schedule::command('cache:cleanup')
    ->dailyAt('01:00')
    ->description('Dọn dẹp cache cũ')
    ->withoutOverlapping();

// Backup database
// Chạy hàng ngày lúc 0:00 AM (nửa đêm)
Schedule::command('db:backup')
    ->dailyAt('00:00')
    ->description('Backup database hàng ngày')
    ->withoutOverlapping()
    ->runInBackground();

// Health check
// Chạy mỗi giờ
Schedule::command('app:health-check')
    ->hourly()
    ->description('Kiểm tra health của hệ thống')
    ->withoutOverlapping();
