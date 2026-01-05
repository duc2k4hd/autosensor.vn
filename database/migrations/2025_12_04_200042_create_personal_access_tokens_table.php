<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id()->comment('Khóa chính token cá nhân');
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name')->comment('Tên token');
            $table->string('token', 64)->comment('Giá trị token');
            $table->text('abilities')->nullable()->comment('Quyền hạn');
            $table->timestamp('last_used_at')->nullable()->comment('Thời điểm cuối cùng dùng token');
            $table->timestamp('expires_at')->nullable()->comment('Thời điểm hết hạn token');
            $table->timestamps();

            $table->unique('token', 'personal_access_tokens_token_unique');
            $table->index(
                ['tokenable_type', 'tokenable_id'],
                'personal_access_tokens_tokenable_type_tokenable_id_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
