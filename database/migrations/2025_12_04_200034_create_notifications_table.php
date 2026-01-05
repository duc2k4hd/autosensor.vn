<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id()->comment('Khóa chính thông báo');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('type', 50)->comment('Loại thông báo: order, comment, contact, voucher, flash_sale, etc.');
            $table->string('title', 255)->comment('Tiêu đề thông báo');
            $table->text('message')->comment('Nội dung thông báo');
            $table->json('data')->nullable()->comment('Dữ liệu bổ sung (JSON)');
            $table->string('link')->nullable()->comment('URL liên quan đến thông báo');
            $table->string('icon', 50)->nullable()->comment('Icon hiển thị (fa-bell, fa-shopping-cart, etc.)');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                ->default('normal')
                ->comment('Mức độ ưu tiên');
            $table->boolean('is_read')->default(false)->comment('Đã đọc chưa');
            $table->timestamp('read_at')->nullable()->comment('Thời điểm đọc');
            $table->timestamps();

            $table->index(['account_id', 'is_read'], 'notifications_account_id_is_read_index');
            $table->index(['type', 'created_at'], 'notifications_type_created_at_index');
            $table->index('created_at', 'notifications_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
