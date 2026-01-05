<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id()->comment('Khóa chính affiliate');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID người dùng tạo mã giới thiệu');
            $table->string('code')->unique()->comment('Mã giới thiệu duy nhất');
            $table->unsignedBigInteger('clicks')->default(0)->comment('Số lượt click');
            $table->unsignedBigInteger('conversions')->default(0)->comment('Số lượt chuyển đổi');
            $table->decimal('commission_rate', 5, 2)->default(0)->comment('Phần trăm hoa hồng (0-100)');
            $table->decimal('total_commission', 15, 2)->default(0)->comment('Hoa hồng tích lũy');
            $table->string('referral_url')->nullable()->comment('URL giới thiệu');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Trạng thái');
            $table->timestamps();

            $table->index('account_id', 'affiliates_account_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
