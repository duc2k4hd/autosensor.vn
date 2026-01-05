<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('User đã đăng nhập');
            $table->string('session_id')->nullable()->comment('Session ID cho user chưa đăng nhập');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['product_id', 'viewed_at'], 'product_views_product_id_viewed_at_index');
            $table->index(['account_id', 'viewed_at'], 'product_views_account_id_viewed_at_index');
            $table->index(['session_id', 'viewed_at'], 'product_views_session_id_viewed_at_index');
            $table->index('viewed_at', 'product_views_viewed_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
