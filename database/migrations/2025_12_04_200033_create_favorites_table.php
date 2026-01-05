<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id()->comment('Khóa chính yêu thích');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm yêu thích');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID tài khoản (nếu có)');
            $table->string('session_id', 100)->nullable()->comment('Session khách vãng lai');
            $table->timestamps();

            $table->unique(['product_id', 'account_id', 'session_id'], 'favorites_unique_owner');
            $table->index(['account_id', 'session_id'], 'favorites_account_id_session_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
