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
        Schema::create('product_wizard_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('product_type', 50)->comment('sensor, plc, inverter');
            $table->json('answers')->comment('Câu trả lời từ wizard');
            $table->json('recommended_product_ids')->nullable()->comment('Danh sách ID sản phẩm được gợi ý');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('product_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_wizard_sessions');
    }
};
