<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('slug_indexes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type', 50)->index(); // product | post | category | ...
            $table->unsignedBigInteger('entity_id')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('target_slug')->nullable(); // dùng cho redirect 301 nếu cần
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_indexes');
    }
};

