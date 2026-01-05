<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id()->comment('Khóa chính cấu hình');
            $table->string('key')->unique()->comment('Khóa cấu hình (duy nhất)');
            $table->text('value')->nullable()->comment('Giá trị cấu hình');
            $table->string('type')->default('string')->comment('Kiểu dữ liệu (string, boolean, image, ...)');
            $table->string('group')->nullable()->comment('Nhóm cấu hình');
            $table->string('label')->nullable()->comment('Nhãn hiển thị');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->boolean('is_public')->default(true)->comment('Có hiển thị công khai hay không');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
