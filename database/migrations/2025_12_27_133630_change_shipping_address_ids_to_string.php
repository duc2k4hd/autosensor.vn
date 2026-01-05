<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chuyển các trường ID địa chỉ từ unsignedBigInteger sang string để tránh mất số 0 đầu.
     */
    public function up(): void
    {
        // Chuyển đổi dữ liệu hiện có sang string trước khi thay đổi kiểu cột
        // Bảng orders
        DB::statement('UPDATE orders SET shipping_province_id = CAST(shipping_province_id AS CHAR) WHERE shipping_province_id IS NOT NULL');
        DB::statement('UPDATE orders SET shipping_district_id = CAST(shipping_district_id AS CHAR) WHERE shipping_district_id IS NOT NULL');
        DB::statement('UPDATE orders SET shipping_ward_id = CAST(shipping_ward_id AS CHAR) WHERE shipping_ward_id IS NOT NULL');

        // Bảng addresses
        DB::statement('UPDATE addresses SET province_code = CAST(province_code AS CHAR) WHERE province_code IS NOT NULL');
        DB::statement('UPDATE addresses SET district_code = CAST(district_code AS CHAR) WHERE district_code IS NOT NULL');

        // Thay đổi kiểu cột trong bảng orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_province_id', 20)->nullable()->change();
            $table->string('shipping_district_id', 20)->nullable()->change();
            $table->string('shipping_ward_id', 20)->nullable()->change();
        });

        // Thay đổi kiểu cột trong bảng addresses
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('province_code', 20)->nullable()->change();
            $table->string('district_code', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Chuyển đổi dữ liệu về integer trước khi thay đổi kiểu cột
        // Bảng orders
        DB::statement('UPDATE orders SET shipping_province_id = CAST(shipping_province_id AS UNSIGNED) WHERE shipping_province_id IS NOT NULL AND shipping_province_id REGEXP "^[0-9]+$"');
        DB::statement('UPDATE orders SET shipping_district_id = CAST(shipping_district_id AS UNSIGNED) WHERE shipping_district_id IS NOT NULL AND shipping_district_id REGEXP "^[0-9]+$"');
        DB::statement('UPDATE orders SET shipping_ward_id = CAST(shipping_ward_id AS UNSIGNED) WHERE shipping_ward_id IS NOT NULL AND shipping_ward_id REGEXP "^[0-9]+$"');

        // Bảng addresses
        DB::statement('UPDATE addresses SET province_code = CAST(province_code AS UNSIGNED) WHERE province_code IS NOT NULL AND province_code REGEXP "^[0-9]+$"');
        DB::statement('UPDATE addresses SET district_code = CAST(district_code AS UNSIGNED) WHERE district_code IS NOT NULL AND district_code REGEXP "^[0-9]+$"');

        // Thay đổi kiểu cột về unsignedBigInteger
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_province_id')->nullable()->change();
            $table->unsignedBigInteger('shipping_district_id')->nullable()->change();
            $table->unsignedBigInteger('shipping_ward_id')->nullable()->change();
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('province_code')->nullable()->change();
            $table->unsignedBigInteger('district_code')->nullable()->change();
        });
    }
};
