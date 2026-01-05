<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id()->comment('Khóa chính mục flash sale');
            $table->foreignId('flash_sale_id')
                ->constrained('flash_sales')
                ->cascadeOnDelete()
                ->comment('ID flash sale');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm');
            $table->decimal('original_price', 15, 2)->nullable()->comment('Giá gốc');
            $table->decimal('sale_price', 15, 2)->comment('Giá sale');
            $table->decimal('unified_price', 15, 2)->nullable()->comment('Giá đồng nhất');
            $table->decimal('original_variant_price', 15, 2)->nullable()->comment('Giá gốc biến thể');
            $table->unsignedInteger('stock')->default(0)->comment('Kho');
            $table->unsignedInteger('sold')->default(0)->comment('Đã bán');
            $table->unsignedInteger('max_per_user')->nullable()->comment('Giới hạn mỗi người');
            $table->boolean('is_active')->default(true)->comment('Kích hoạt');
            $table->unsignedInteger('sort_order')->default(0)->comment('Thứ tự');
            $table->timestamps();

            $table->index('product_id', 'flash_sale_items_product_id_index');
            $table->index('flash_sale_id', 'flash_sale_items_flash_sale_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_items');
    }
};
