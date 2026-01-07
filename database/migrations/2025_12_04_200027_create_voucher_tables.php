<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id()->comment('Khóa chính voucher');
            $table->string('code')->unique()->comment('Mã voucher');
            $table->string('name')->nullable()->comment('Tên voucher');
            $table->text('description')->nullable()->comment('Mô tả voucher');
            $table->string('image')->nullable()->comment('Ảnh hiển thị');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Người tạo');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Người cập nhật');
            $table->enum('type', ['percent', 'fixed', 'free_shipping'])
                ->default('percent')
                ->comment('Loại voucher');
            $table->decimal('value', 10, 2)->default(0)->comment('Giá trị voucher');
            $table->decimal('max_discount', 10, 2)->nullable()->comment('Giảm tối đa');
            $table->unsignedInteger('min_order_value')->default(0)->comment('Giá trị đơn tối thiểu');
            $table->integer('usage_limit')->nullable()->comment('Giới hạn dùng tối đa');
            $table->integer('usage_limit_per_user')->nullable()->comment('Giới hạn mỗi người');
            $table->dateTime('start_time')->nullable()->comment('Thời gian bắt đầu');
            $table->dateTime('end_time')->nullable()->comment('Thời gian kết thúc');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->json('apply_for')->nullable()->comment('Điều kiện áp dụng (JSON)');
            $table->timestamps();

            $table->index('account_id', 'vouchers_account_id_index');
        });

        Schema::create('voucher_histories', function (Blueprint $table) {
            $table->id()->comment('Khóa chính lịch sử dùng voucher');
            $table->foreignId('voucher_id')
                ->constrained('vouchers')
                ->cascadeOnDelete();
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('Số tiền giảm');
            $table->string('ip')->nullable()->comment('IP dùng voucher');
            $table->string('session_id')->nullable()->comment('Session khách');
            $table->timestamps();

            $table->index('voucher_id', 'voucher_histories_voucher_id_index');
            $table->index('order_id', 'voucher_histories_order_id_index');
            $table->index('account_id', 'voucher_histories_account_id_index');
        });

        Schema::create('voucher_user_usages', function (Blueprint $table) {
            $table->id()->comment('Khóa chính thống kê sử dụng voucher');
            $table->foreignId('voucher_id')
                ->constrained('vouchers')
                ->cascadeOnDelete();
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('session_id')->nullable()->comment('Session khách vãng lai');
            $table->integer('usage_count')->default(0)->comment('Số lần dùng');
            $table->timestamps();

            $table->unique(['voucher_id', 'account_id', 'session_id'], 'voucher_user_usages_unique');
            $table->index('voucher_id', 'voucher_user_usages_voucher_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_user_usages');
        Schema::dropIfExists('voucher_histories');
        Schema::dropIfExists('vouchers');
    }
};
