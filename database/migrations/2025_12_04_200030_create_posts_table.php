<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id()->comment('Khóa chính bài viết');
            $table->string('title')->comment('Tiêu đề bài viết');
            $table->string('slug')->comment('Slug thân thiện URL');
            $table->string('meta_title')->nullable()->comment('Meta title SEO');
            $table->text('meta_description')->nullable()->comment('Meta description SEO');
            $table->text('meta_keywords')->nullable()->comment('Meta keywords SEO');
            $table->string('meta_canonical')->nullable()->comment('Canonical URL');
            $table->json('tag_ids')->nullable()->comment('Danh sách tag (JSON)');
            $table->text('excerpt')->nullable()->comment('Tóm tắt bài viết');
            $table->longText('content')->nullable()->comment('Nội dung chi tiết');
            $table->json('image_ids')->nullable()->comment('Danh sách ảnh (JSON)');
            $table->enum('status', ['draft', 'pending', 'published', 'archived'])
                ->default('draft')
                ->comment('Trạng thái xuất bản');
            $table->boolean('is_featured')->default(false)->comment('Đánh dấu bài viết nổi bật');
            $table->unsignedBigInteger('views')->default(0)->comment('Lượt xem');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID tài khoản sở hữu');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->comment('ID danh mục bài viết');
            $table->timestamp('published_at')->nullable()->comment('Thời điểm xuất bản');
            $table->foreignId('created_by')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->comment('ID người tạo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug', 'posts_slug_unique');
            $table->index('account_id', 'posts_account_id_foreign');
            $table->index('category_id', 'posts_category_id_foreign');
            $table->index('created_by', 'posts_created_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
