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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('primary_category_id')
                ->constrained('brands')
                ->nullOnDelete()
                ->comment('Thương hiệu sản phẩm');
            
            $table->index('brand_id', 'products_brand_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropIndex('products_brand_id_index');
            $table->dropColumn('brand_id');
        });
    }
};
