<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class IncreasePricePrecisionInPaymentsTable extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payments MODIFY amount DECIMAL(15,2) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments MODIFY amount DECIMAL(10,2) NOT NULL');
    }
}
