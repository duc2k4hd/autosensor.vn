<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Phục vụ truy vấn list bình luận gốc theo sản phẩm:
            // where commentable_type, commentable_id, parent_id, is_approved
            // order by created_at desc, limit 10
            $table->index(
                ['commentable_type', 'commentable_id', 'parent_id', 'is_approved', 'created_at'],
                'comments_product_roots_idx'
            );

            // Phục vụ truy vấn thống kê rating:
            // where commentable_type, commentable_id, parent_id, is_approved, rating
            $table->index(
                ['commentable_type', 'commentable_id', 'parent_id', 'is_approved', 'rating'],
                'comments_product_ratings_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_product_roots_idx');
            $table->dropIndex('comments_product_ratings_idx');
        });
    }
};

