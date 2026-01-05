<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_how_tos', function (Blueprint $table) {
            $table->id()->comment('Khóa chính hướng dẫn sử dụng');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm');
            $table->string('title')->comment('Tiêu đề hướng dẫn');
            $table->text('description')->nullable()->comment('Mô tả tổng quan');
            $table->json('steps')->nullable()->comment('Danh sách bước (JSON)');
            $table->json('supplies')->nullable()->comment('Dụng cụ cần thiết (JSON)');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hiển thị');
            $table->timestamps();

            $table->index('product_id', 'product_how_tos_product_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_how_tos');
    }
};
