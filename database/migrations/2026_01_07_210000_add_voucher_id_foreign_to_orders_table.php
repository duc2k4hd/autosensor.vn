<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // đảm bảo cột tồn tại
            if (!Schema::hasColumn('orders', 'voucher_id')) {
                $table->unsignedBigInteger('voucher_id')->nullable()->after('admin_note');
            }
        });

        // Kiểm tra xem foreign key đã tồn tại chưa trước khi thêm
        $foreignKeys = Schema::getConnection()
            ->select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'orders' 
                      AND CONSTRAINT_NAME = 'orders_voucher_id_foreign'");
        
        if (empty($foreignKeys) && Schema::hasTable('vouchers')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('voucher_id', 'orders_voucher_id_foreign')
                    ->references('id')
                    ->on('vouchers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropForeign('orders_voucher_id_foreign');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};

