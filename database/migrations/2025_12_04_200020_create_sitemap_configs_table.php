<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_configs', function (Blueprint $table) {
            $table->id()->comment('Khóa chính cấu hình sitemap');
            $table->string('config_key')->unique();
            $table->text('config_value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_configs');
    }
};
