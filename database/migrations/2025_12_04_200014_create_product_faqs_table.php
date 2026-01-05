<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_faqs', function (Blueprint $table) {
            $table->id()->comment('Khóa chính FAQ sản phẩm');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('ID sản phẩm');
            $table->string('question')->comment('Câu hỏi của khách hàng');
            $table->text('answer')->nullable()->comment('Câu trả lời');
            $table->integer('order')->default(0)->comment('Thứ tự');
            $table->timestamps();

            $table->index('product_id', 'product_faqs_product_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_faqs');
    }
};
