<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_price_logs', function (Blueprint $table) {
            $table->id()->comment('Khóa chính');
            $table->foreignId('flash_sale_item_id')
                ->constrained('flash_sale_items')
                ->cascadeOnDelete()
                ->comment('ID flash sale item');
            $table->decimal('old_price', 15, 2)->comment('Giá cũ');
            $table->decimal('new_price', 15, 2)->comment('Giá mới');
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Người thay đổi');
            $table->timestamp('changed_at')->nullable()->comment('Thời gian thay đổi');
            $table->text('reason')->nullable()->comment('Lý do thay đổi');
            $table->timestamps();

            $table->index('flash_sale_item_id', 'flash_sale_price_logs_item_id_index');
            $table->index('changed_by', 'flash_sale_price_logs_changed_by_index');
            $table->index('changed_at', 'flash_sale_price_logs_changed_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_price_logs');
    }
};
