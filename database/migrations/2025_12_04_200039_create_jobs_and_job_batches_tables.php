<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('Khóa chính job queue');
            $table->string('queue')->comment('Tên queue');
            $table->longText('payload')->comment('Payload job');
            $table->unsignedTinyInteger('attempts')->comment('Số lần thử');
            $table->unsignedInteger('reserved_at')->nullable()->comment('Thời điểm reserve');
            $table->unsignedInteger('available_at')->comment('Thời điểm có thể chạy');
            $table->unsignedInteger('created_at')->comment('Thời điểm tạo job');

            $table->index(['queue', 'reserved_at'], 'jobs_queue_reserved_at_index');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('ID batch');
            $table->string('name')->comment('Tên batch');
            $table->integer('total_jobs')->comment('Tổng số job');
            $table->integer('pending_jobs')->comment('Số job đang chờ');
            $table->integer('failed_jobs')->comment('Số job lỗi');
            $table->longText('failed_job_ids')->comment('Danh sách job lỗi');
            $table->mediumText('options')->nullable()->comment('Tùy chọn');
            $table->integer('cancelled_at')->nullable()->comment('Thời điểm hủy');
            $table->integer('created_at')->comment('Thời điểm tạo');
            $table->integer('finished_at')->nullable()->comment('Thời điểm hoàn tất');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
