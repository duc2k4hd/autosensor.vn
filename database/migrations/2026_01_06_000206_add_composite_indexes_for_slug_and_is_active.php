<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thêm composite index cho products (slug, is_active) để tối ưu query
        // Với hàng triệu records, index này giúp query where('slug', $slug)->where('is_active', true) nhanh hơn
        Schema::table('products', function (Blueprint $table) {
            $table->index(['slug', 'is_active'], 'products_slug_is_active_index');
        });

        // Thêm composite index cho categories (slug, is_active)
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['slug', 'is_active'], 'categories_slug_is_active_index');
        });

        // Thêm index cho product_slug_histories.slug để tối ưu lookup
        Schema::table('product_slug_histories', function (Blueprint $table) {
            $table->index('slug', 'product_slug_histories_slug_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_slug_is_active_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_slug_is_active_index');
        });

        Schema::table('product_slug_histories', function (Blueprint $table) {
            $table->dropIndex('product_slug_histories_slug_index');
        });
    }
};
