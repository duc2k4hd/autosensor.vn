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
        Schema::create('quick_consultation_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->text('message')->nullable();
            $table->string('trigger_type', 50)->comment('view_time, multiple_products, manual');
            $table->json('behavior_data')->nullable()->comment('Thời gian xem, số sản phẩm đã xem, category_ids, etc.');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->boolean('is_contacted')->default(false);
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('phone');
            $table->index('session_id');
            $table->index('created_at');
            $table->index('is_contacted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_consultation_leads');
    }
};
