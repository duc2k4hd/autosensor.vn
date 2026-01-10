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
        Schema::table('product_wizard_sessions', function (Blueprint $table) {
            // Thêm cột category_id
            $table->unsignedBigInteger('category_id')->nullable()->after('id');
        });
        
        // Migrate dữ liệu cũ (nếu có)
        $this->migrateOldData();
        
        Schema::table('product_wizard_sessions', function (Blueprint $table) {
            // Xóa cột product_type cũ
            $table->dropColumn('product_type');
            
            // Đổi category_id thành NOT NULL và thêm index
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->index('category_id');
        });
    }

    /**
     * Migrate dữ liệu cũ
     */
    protected function migrateOldData(): void
    {
        $mapping = [
            'sensor' => 1,  // Cảm biến
            'plc' => 2,     // PLC
            'inverter' => 3, // Biến tần
        ];

        foreach ($mapping as $oldType => $categoryId) {
            \DB::table('product_wizard_sessions')
                ->where('product_type', $oldType)
                ->update(['category_id' => $categoryId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_wizard_sessions', function (Blueprint $table) {
            // Thêm lại cột product_type
            $table->string('product_type', 50)->after('id');
            
            // Migrate dữ liệu ngược lại
            $this->rollbackData();
            
            // Xóa cột category_id
            $table->dropColumn('category_id');
        });
    }

    /**
     * Rollback dữ liệu
     */
    protected function rollbackData(): void
    {
        $mapping = [
            1 => 'sensor',
            2 => 'plc',
            3 => 'inverter',
        ];

        foreach ($mapping as $categoryId => $oldType) {
            \DB::table('product_wizard_sessions')
                ->where('category_id', $categoryId)
                ->update(['product_type' => $oldType]);
        }
    }
};
