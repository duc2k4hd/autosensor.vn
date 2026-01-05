<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session ID');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID tài khoản');
            $table->string('ip_address', 45)->nullable()->comment('IP đăng nhập');
            $table->text('user_agent')->nullable()->comment('Thông tin trình duyệt');
            $table->text('payload')->comment('Dữ liệu session');
            $table->integer('last_activity')->comment('Hoạt động cuối (timestamp)');

            $table->index('account_id', 'sessions_account_id_index');
            $table->index('last_activity', 'sessions_last_activity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
