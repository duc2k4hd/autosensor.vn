<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_staff', function (Blueprint $table) {
            if (! Schema::hasColumn('support_staff', 'avatar')) {
                $table->string('avatar')->nullable()->after('color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_staff', function (Blueprint $table) {
            if (Schema::hasColumn('support_staff', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });
    }
};

