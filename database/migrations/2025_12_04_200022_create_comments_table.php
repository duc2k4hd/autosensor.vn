<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id()->comment('Khóa chính bình luận');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID tài khoản bình luận');
            $table->string('session_id', 100)->nullable()->comment('Session của khách vãng lai');
            $table->unsignedBigInteger('commentable_id')->comment('ID đối tượng được bình luận');
            $table->string('commentable_type')->comment('Kiểu model (Post/Product/...)');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->nullOnDelete()
                ->comment('ID bình luận cha');
            $table->text('content')->comment('Nội dung bình luận');
            $table->string('name')->nullable()->comment('Tên người bình luận (khách)');
            $table->string('email')->nullable()->comment('Email khách');
            $table->boolean('is_approved')->default(true)->comment('Duyệt bình luận');
            $table->integer('rating')->nullable()->comment('Đánh giá (1-5, chỉ áp dụng cho sản phẩm)');
            $table->string('ip')->nullable()->comment('IP người bình luận');
            $table->text('user_agent')->nullable()->comment('User agent của người bình luận');
            $table->boolean('is_reported')->default(false)->comment('Đã báo cáo');
            $table->integer('reports_count')->default(0)->comment('Số lần báo cáo');
            $table->timestamps();

            $table->index('commentable_id', 'comments_commentable_id_index');
            $table->index('commentable_type', 'comments_commentable_type_index');
            $table->index('parent_id', 'comments_parent_id_index');
            $table->index('account_id', 'comments_account_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
