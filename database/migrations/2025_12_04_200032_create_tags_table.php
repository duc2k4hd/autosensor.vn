<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id()->comment('Khóa chính tag');
            $table->string('name')->comment('Tên tag');
            $table->string('slug')->comment('Slug tag');
            $table->text('description')->nullable()->comment('Mô tả tag');
            $table->boolean('is_active')->default(true)->comment('Kích hoạt');
            $table->unsignedBigInteger('usage_count')->default(0)->comment('Số lần dùng');
            $table->unsignedBigInteger('entity_id')->comment('ID của entity');
            $table->string('entity_type')->comment('Loại entity: product/post/...');
            $table->timestamps();

            $table->unique('slug', 'tags_slug_unique');
            $table->index(['entity_id', 'entity_type'], 'tags_entity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
