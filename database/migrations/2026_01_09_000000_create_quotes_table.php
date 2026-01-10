<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Tài khoản (nếu đã đăng nhập)');

            $table->string('name')->comment('Tên người yêu cầu báo giá');
            $table->string('email')->nullable()->comment('Email nhận báo giá');
            $table->string('phone')->nullable()->comment('Điện thoại liên hệ');

            $table->text('note')->nullable()->comment('Ghi chú thêm từ khách hàng');

            $table->decimal('total_amount', 15, 2)->default(0)->comment('Tổng giá trị giỏ tại thời điểm yêu cầu');
            $table->json('cart_snapshot')->comment('Snapshot giỏ hàng tại thời điểm yêu cầu (sản phẩm, số lượng, giá)');

            $table->string('status', 50)
                ->default('new')
                ->comment('new|contacted|done|cancelled');

            $table->string('pdf_path')->nullable()->comment('Đường dẫn file PDF báo giá đã sinh (nếu có)');

            $table->string('ip', 45)->nullable()->comment('IP khách tại thời điểm yêu cầu');
            $table->text('user_agent')->nullable()->comment('User agent lúc yêu cầu');

            $table->timestamps();

            $table->index('account_id', 'quotes_account_id_index');
            $table->index('status', 'quotes_status_index');
            $table->index('created_at', 'quotes_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};

