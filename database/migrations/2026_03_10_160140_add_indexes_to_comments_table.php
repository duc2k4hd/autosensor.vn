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
        Schema::table('comments', function (Blueprint $table) {
            // Index phục vụ danh sách chính (lọc root + sắp xếp)
            $table->index(['parent_id', 'created_at'], 'comments_parent_id_created_at_index');

            // Index phục vụ quan hệ đa hình (Morph)
            $table->index(['commentable_type', 'commentable_id'], 'comments_commentable_composite_index');

            // Index phục vụ thống kê (Stats)
            $table->index(['is_approved', 'parent_id', 'rating'], 'comments_stats_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_parent_id_created_at_index');
            $table->dropIndex('comments_commentable_composite_index');
            $table->dropIndex('comments_stats_composite_index');
        });
    }
};
