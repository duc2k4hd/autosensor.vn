<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id()->comment('Khóa chính sản phẩm trong đơn');
            $table->uuid('uuid')->nullable()->comment('Mã định danh item');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete()
                ->comment('ID đơn hàng');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm');
            $table->boolean('is_flash_sale')->default(false)->comment('Đánh dấu sản phẩm flash sale');
            $table->unsignedBigInteger('flash_sale_item_id')->nullable()->comment('ID flash sale item (nếu có)');
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('ID biến thể sản phẩm (nếu có)');
            $table->unsignedInteger('quantity')->comment('Số lượng');
            $table->decimal('price', 15, 2)->comment('Giá tại thời điểm mua');
            $table->decimal('total', 15, 2)->comment('Thành tiền');
            $table->json('options')->nullable()->comment('Thuộc tính sản phẩm');
            $table->timestamps();

            $table->index('order_id', 'order_items_order_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
