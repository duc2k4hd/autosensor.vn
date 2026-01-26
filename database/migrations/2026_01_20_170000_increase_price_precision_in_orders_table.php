<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class IncreasePricePrecisionInOrdersTable extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders MODIFY total_price DECIMAL(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE orders MODIFY final_price DECIMAL(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE orders MODIFY voucher_discount DECIMAL(15,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders MODIFY total_price DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE orders MODIFY final_price DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE orders MODIFY voucher_discount DECIMAL(10,2) NOT NULL DEFAULT 0');
    }
}
