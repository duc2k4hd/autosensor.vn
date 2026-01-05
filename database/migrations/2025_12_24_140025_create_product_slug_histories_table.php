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
        Schema::create('product_slug_histories', function (Blueprint $table) {
            $table->id();
            // Không dùng foreign key để tránh lỗi ràng buộc FK trên host
            // Chỉ lưu product_id dạng big integer + index để truy vấn nhanh
            $table->unsignedBigInteger('product_id');
            $table->string('slug')
                ->unique()
                ->comment('Old slug dẫn về product_id');
            $table->timestamps();

            $table->index('product_id', 'product_slug_histories_product_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_slug_histories');
    }
};
