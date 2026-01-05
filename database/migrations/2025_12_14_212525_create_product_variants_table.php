<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name')->comment('Tên variant: 1m, 2m, 5m, etc.');
            $table->string('sku')->nullable()->unique()->comment('SKU riêng cho variant');
            $table->decimal('price', 12, 2)->default(0)->comment('Giá gốc');
            $table->decimal('sale_price', 12, 2)->nullable()->comment('Giá khuyến mãi');
            $table->decimal('cost_price', 12, 2)->nullable()->comment('Giá vốn');
            $table->integer('stock_quantity')->nullable()->comment('Số lượng tồn kho (null = không giới hạn)');
            $table->foreignId('image_id')->nullable()->constrained('images')->onDelete('set null')->comment('Ảnh riêng cho variant');
            $table->json('attributes')->nullable()->comment('Thuộc tính bổ sung: {"height": "1m", "weight": "2kg"}');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
