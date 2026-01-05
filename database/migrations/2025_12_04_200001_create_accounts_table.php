<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id()->comment('Khóa chính tự tăng của tài khoản');
            $table->string('name', 50)->nullable()->comment('Tên hiển thị của người dùng');
            $table->string('email', 80)->unique()->comment('Email đăng nhập duy nhất');
            $table->string('phone', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable()->comment('Thời điểm xác thực email');
            $table->string('password', 191)->comment('Mật khẩu đã băm');
            $table->string('role')->default('user')->comment('Vai trò tài khoản');
            $table->string('remember_token', 100)->nullable()->comment('Token ghi nhớ đăng nhập');
            $table->timestamp('last_password_changed_at')->nullable()->comment('Lần thay đổi mật khẩu gần nhất');
            $table->unsignedInteger('login_attempts')->default(0)->comment('Số lần đăng nhập thất bại');
            $table->enum('status', ['active', 'inactive', 'suspended', 'locked', 'banned'])
                ->default('active')
                ->comment('Trạng thái tài khoản');
            $table->text('admin_note')->nullable();
            $table->json('tags')->nullable();
            $table->json('security_flags')->nullable()->comment('Các cờ bảo mật bổ sung');
            $table->timestamp('login_history')->nullable()->comment('Thời điểm đăng nhập gần nhất');
            $table->text('logs')->nullable()->comment('Ghi chú nội bộ liên quan tới tài khoản');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
