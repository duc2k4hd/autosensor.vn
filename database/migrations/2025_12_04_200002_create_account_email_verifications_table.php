<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_email_verifications', function (Blueprint $table) {
            $table->id()->comment('Khóa chính của phiên xác thực email');
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->string('token', 80)->unique()->comment('Mã token gửi qua email');
            $table->timestamp('expires_at')->comment('Thời điểm token hết hạn');
            $table->timestamp('created_at')->useCurrent()->comment('Thời điểm tạo token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_email_verifications');
    }
};
