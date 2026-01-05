<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->integer('quantity_change')->comment('Số lượng +/- thay đổi tồn kho');
            $table->integer('stock_before')->nullable()->comment('Tồn kho trước khi cập nhật');
            $table->integer('stock_after')->nullable()->comment('Tồn kho sau khi cập nhật');
            $table->string('type', 50)->comment('order, order_cancel, import, export, adjust, system');
            $table->string('reference_type')->nullable()->comment('Model liên quan: Order, ImportReceipt...');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID bản ghi liên quan');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Người thao tác (admin)');
            $table->string('note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(
                ['product_id', 'created_at'],
                'inventory_movements_product_id_created_at_index'
            );
            $table->index(
                ['reference_type', 'reference_id'],
                'inventory_movements_reference_type_reference_id_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
