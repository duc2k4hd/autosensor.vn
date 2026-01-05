<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id()->comment('Khóa chính mục giỏ hàng');
            $table->uuid('uuid')->nullable()->comment('Mã định danh duy nhất của item');
            $table->foreignId('cart_id')
                ->constrained('carts')
                ->cascadeOnDelete()
                ->comment('ID giỏ hàng');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm');
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('ID biến thể sản phẩm (nếu có)');
            $table->unsignedInteger('quantity')->default(1)->comment('Số lượng');
            $table->decimal('price', 15, 2)->comment('Giá tại thời điểm thêm vào giỏ');
            $table->decimal('total_price', 10, 0)->default(0)->comment('Thành tiền = price * quantity');
            $table->json('options')->nullable()->comment('Thuộc tính bổ sung (size, màu...)');
            $table->enum('status', ['active', 'removed'])->default('active');
            $table->boolean('is_flash_sale')->default(false)->comment('Đánh dấu sản phẩm flash sale');
            $table->unsignedBigInteger('flash_sale_item_id')->nullable()->comment('ID flash sale item tương ứng (nếu có)');
            $table->timestamps();

            $table->index('cart_id', 'cart_items_cart_id_index');
            $table->index('product_id', 'cart_items_product_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
