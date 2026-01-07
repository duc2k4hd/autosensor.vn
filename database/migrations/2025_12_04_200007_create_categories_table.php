<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('Khóa chính danh mục');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->comment('Danh mục cha');
            $table->string('name')->comment('Tên danh mục');
            $table->string('slug')->unique()->comment('Slug duy nhất');
            $table->text('description')->nullable()->comment('Mô tả danh mục');
            $table->string('image')->nullable()->comment('Ảnh đại diện danh mục');
            $table->unsignedInteger('order')->default(0)->comment('Thứ tự hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('metadata')->nullable()->comment('Meta SEO JSON');
            $table->timestamps();

            $table->index('parent_id', 'categories_parent_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
