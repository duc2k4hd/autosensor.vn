<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id()->comment('Khóa chính chương trình flash sale');
            $table->string('title')->comment('Tên chương trình');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->string('banner')->nullable()->comment('Ảnh banner');
            $table->string('tag', 50)->nullable()->comment('Tag hiển thị');
            $table->timestamp('start_time')->comment('Bắt đầu');
            $table->timestamp('end_time')->comment('Kết thúc');
            $table->enum('status', ['draft', 'active', 'expired'])
                ->default('draft')
                ->comment('Trạng thái');
            $table->boolean('is_active')->default(true)->comment('Kích hoạt?');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Người tạo');
            $table->unsignedInteger('max_per_user')->nullable()->comment('Giới hạn mỗi người');
            $table->unsignedInteger('display_limit')->default(20)->comment('Giới hạn hiển thị');
            $table->enum('product_add_mode', ['auto_by_category', 'manual'])
                ->nullable()
                ->comment('Cách thêm sản phẩm');
            $table->unsignedBigInteger('views')->default(0)->comment('Lượt xem');
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by', 'flash_sales_created_by_foreign');
            $table->index('status', 'flash_sales_status_index');
            $table->index('start_time', 'flash_sales_start_time_index');
            $table->index('end_time', 'flash_sales_end_time_index');
            $table->index(
                ['status', 'is_active', 'start_time', 'end_time'],
                'flash_sales_status_is_active_start_time_end_time_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
