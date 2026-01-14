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
        Schema::table('tags', function (Blueprint $table) {
            // Đổi cột name từ string (VARCHAR(255)) sang text để cho phép lưu tên tag dài bao nhiêu cũng được
            $table->text('name')->change()->comment('Tên tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // Rollback: đổi lại thành string (VARCHAR(255))
            $table->string('name')->change()->comment('Tên tag');
        });
    }
};
