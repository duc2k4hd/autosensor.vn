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

            // thêm FK (sau khi bảng vouchers đã có)
            $table->foreign('voucher_id', 'orders_voucher_id_foreign')
                ->references('id')
                ->on('vouchers')
                ->nullOnDelete();
        });
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

