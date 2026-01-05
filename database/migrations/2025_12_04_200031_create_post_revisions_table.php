<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id()->comment('Khóa chính bản nháp bài viết');
            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete()
                ->comment('ID bài viết gốc');
            $table->foreignId('edited_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID người chỉnh sửa');
            $table->string('title')->nullable()->comment('Tiêu đề phiên bản');
            $table->longText('content')->nullable()->comment('Nội dung phiên bản');
            $table->text('excerpt')->nullable()->comment('Tóm tắt phiên bản');
            $table->json('meta')->nullable()->comment('Dữ liệu meta (JSON)');
            $table->boolean('is_autosave')->default(false)->comment('Đánh dấu autosave');
            $table->timestamps();

            $table->index('post_id', 'post_revisions_post_id_foreign');
            $table->index('edited_by', 'post_revisions_edited_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_revisions');
    }
};
