<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('affiliate_code')->nullable()->after('voucher_code')->comment('Mã affiliate giới thiệu');
            $table->index('affiliate_code', 'orders_affiliate_code_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_affiliate_code_index');
            $table->dropColumn('affiliate_code');
        });
    }
};
