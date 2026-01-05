<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id()->comment('Khóa chính giỏ hàng');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID tài khoản (nếu đăng nhập)');
            $table->string('session_id')->nullable()->comment('ID session khách vãng lai');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm');
            $table->json('options')->nullable()->comment('Tùy chọn sản phẩm (màu, size...)');
            $table->enum('status', ['active', 'ordered', 'abandoned'])
                ->default('active');
            $table->timestamps();

            $table->index('account_id', 'carts_account_id_index');
            $table->index('session_id', 'carts_session_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
