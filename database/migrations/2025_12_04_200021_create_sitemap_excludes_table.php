<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_excludes', function (Blueprint $table) {
            $table->id()->comment('Khóa chính loại trừ sitemap');
            $table->string('type', 50)->default('url');
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'value'], 'sitemap_excludes_type_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_excludes');
    }
};
