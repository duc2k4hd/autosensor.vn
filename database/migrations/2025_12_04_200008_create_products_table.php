<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('Khóa chính sản phẩm');
            $table->string('sku')->nullable()->comment('Mã SKU (không biến thể)');
            $table->string('name')->comment('Tên sản phẩm');
            $table->string('slug')->comment('Slug sản phẩm');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->text('short_description')->nullable()->comment('Mô tả ngắn');
            $table->decimal('price', 10, 2)->comment('Giá bán niêm yết');
            $table->decimal('sale_price', 10, 2)->nullable()->comment('Giá khuyến mãi');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Giá vốn');
            $table->integer('stock_quantity')->default(0)->comment('Tồn kho hiện tại');
            $table->text('meta_title')->nullable()->comment('Meta title SEO');
            $table->text('meta_description')->nullable()->comment('Meta description SEO');
            $table->json('meta_keywords')->nullable()->comment('Meta keywords (JSON)');
            $table->text('meta_canonical')->nullable()->comment('Canonical URL');
            $table->foreignId('primary_category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->comment('Danh mục chính');
            $table->json('category_included_ids')->nullable()->comment('Danh sách danh mục dùng để gợi ý sản phẩm đi kèm');
            $table->json('category_ids')->nullable()->comment('Danh sách danh mục (JSON)');
            $table->json('tag_ids')->nullable()->comment('Danh sách tag (JSON)');
            $table->json('image_ids')->nullable()->comment('Danh sách ảnh (JSON)');
            $table->boolean('is_featured')->default(false)->comment('Sản phẩm nổi bật');
            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID người đang khóa chỉnh sửa');
            $table->timestamp('locked_at')->nullable()->comment('Thời điểm khóa');
            $table->foreignId('created_by')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->comment('ID người tạo sản phẩm');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hiển thị');
            $table->json('category_ids_backup')->nullable()->comment('Backup danh mục (nếu cần)');
            $table->timestamps();

            $table->unique('slug', 'products_slug_unique');
            $table->unique('sku', 'products_sku_unique');
            $table->index('created_by', 'products_created_by_index');
            $table->index('locked_by', 'products_locked_by_foreign');
            $table->index('primary_category_id', 'products_primary_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
