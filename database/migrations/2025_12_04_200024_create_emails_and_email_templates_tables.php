<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id()->comment('Khóa chính tài khoản email gửi đi');
            $table->string('email')->unique()->comment('Địa chỉ email gửi');
            $table->string('name')->comment('Tên hiển thị');
            $table->text('description')->nullable()->comment('Mô tả thêm');
            $table->boolean('is_default')->default(false)->comment('Đánh dấu làm tài khoản mặc định');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->integer('order')->default(0)->comment('Thứ tự ưu tiên');
            $table->string('mail_host')->nullable()->comment('SMTP Host');
            $table->integer('mail_port')->nullable()->comment('SMTP Port');
            $table->string('mail_username')->nullable()->comment('SMTP Username');
            $table->text('mail_password')->nullable()->comment('SMTP Password');
            $table->string('mail_encryption')->nullable()->comment('Kiểu mã hóa (ssl/tls)');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Unique key: order_confirmation, password_reset, etc.');
            $table->string('name')->comment('Tên template');
            $table->string('subject')->comment('Subject email');
            $table->text('body')->comment('Nội dung email (HTML)');
            $table->text('variables')->nullable()->comment('JSON: Danh sách biến có thể dùng');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('emails');
    }
};
