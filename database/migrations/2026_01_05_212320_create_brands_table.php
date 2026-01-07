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
        Schema::create('brands', function (Blueprint $table) {
            $table->id()->comment('Khóa chính thương hiệu');
            $table->string('name')->comment('Tên thương hiệu');
            $table->string('slug')->unique()->comment('Slug duy nhất');
            $table->text('description')->nullable()->comment('Mô tả thương hiệu');
            $table->string('image')->nullable()->comment('Logo thương hiệu');
            $table->unsignedInteger('order')->default(0)->comment('Thứ tự hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('metadata')->nullable()->comment('Meta SEO JSON (meta_title, meta_description, meta_keywords)');
            $table->text('website')->nullable()->comment('Website thương hiệu');
            $table->text('country')->nullable()->comment('Quốc gia xuất xứ');
            $table->timestamps();

            $table->index('is_active', 'brands_is_active_index');
            $table->index('order', 'brands_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
