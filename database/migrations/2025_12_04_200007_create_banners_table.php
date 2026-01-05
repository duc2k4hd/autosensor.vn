<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id()->comment('Khóa chính banner');
            $table->string('title')->nullable()->comment('Tiêu đề banner');
            $table->text('description')->nullable()->comment('Mô tả banner');
            $table->string('image_desktop')->comment('Đường dẫn hình ảnh');
            $table->string('image_mobile')->nullable()->comment('Đường dẫn hình ảnh mobile');
            $table->string('link')->nullable()->comment('Liên kết khi click vào banner');
            $table->string('target', 10)->default('_blank')->comment('Target của link (_blank, _self)');
            $table->string('position')->nullable()->comment('Vị trí hiển thị trên trang');
            $table->integer('order')->default(0)->comment('Thứ tự hiển thị');
            $table->timestamp('start_at')->nullable()->comment('Thời gian bắt đầu hiển thị');
            $table->timestamp('end_at')->nullable()->comment('Thời gian kết thúc hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->timestamps();
            $table->softDeletes();

            $table->index('start_at', 'banners_start_at_index');
            $table->index('end_at', 'banners_end_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
