<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_logs', function (Blueprint $table) {
            $table->id()->comment('Khóa chính log');
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->comment('ID tài khoản');
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID quản trị');
            $table->string('type')->comment('Loại hành động');
            $table->json('payload')->nullable()->comment('Dữ liệu chi tiết');
            $table->string('ip')->nullable()->comment('IP thực hiện');
            $table->string('user_agent')->nullable()->comment('User-agent');
            $table->timestamps();

            $table->index('account_id', 'account_logs_account_id_index');
            $table->index('admin_id', 'account_logs_admin_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_logs');
    }
};
