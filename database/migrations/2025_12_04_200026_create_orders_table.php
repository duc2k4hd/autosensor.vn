<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('Khóa chính đơn hàng');
            $table->string('code')->unique()->comment('Mã đơn hàng');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID tài khoản đặt hàng');
            $table->string('session_id', 100)->nullable()->comment('Session khách vãng lai');
            $table->decimal('total_price', 10, 2)->default(0)->comment('Tổng tiền hàng');
            $table->foreignId('shipping_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();
            $table->foreignId('billing_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Tổng tiền hàng');
            $table->decimal('discount', 15, 2)->default(0)->comment('Giảm giá');
            $table->decimal('voucher_discount', 10, 2)->default(0)->comment('Giảm giá từ voucher');
            $table->string('voucher_code')->nullable()->comment('Mã voucher áp dụng');
            $table->decimal('final_price', 10, 2)->default(0)->comment('Tổng thanh toán cuối cùng');
            $table->string('receiver_name')->nullable()->comment('Tên người nhận');
            $table->string('receiver_phone')->nullable()->comment('Số điện thoại người nhận');
            $table->string('receiver_email')->nullable()->comment('Email người nhận');
            $table->string('shipping_address')->nullable()->comment('Địa chỉ giao hàng');
            $table->unsignedBigInteger('shipping_province_id')->nullable()->comment('Mã tỉnh (shipping)');
            $table->unsignedBigInteger('shipping_district_id')->nullable()->comment('Mã quận (shipping)');
            $table->unsignedBigInteger('shipping_ward_id')->nullable()->comment('Mã phường (shipping)');
            $table->decimal('shipping_fee', 15, 2)->default(0)->comment('Phí vận chuyển');
            $table->decimal('tax', 10, 2)->default(0)->comment('Thuế áp dụng');
            $table->decimal('total', 15, 2)->default(0)->comment('Tổng thanh toán');
            $table->string('payment_method')->nullable()->comment('Phương thức thanh toán');
            $table->string('payment_status')->default('pending')->comment('Trạng thái thanh toán');
            $table->string('transaction_code')->nullable()->comment('Mã giao dịch từ cổng thanh toán');
            $table->string('shipping_partner')->nullable()->comment('Đối tác vận chuyển');
            $table->text('shipping_tracking_code')->nullable()->comment('Mã vận đơn');
            $table->json('shipping_raw_response')->nullable()->comment('Phản hồi gốc từ đối tác vận chuyển');
            $table->string('delivery_status')->default('pending')->comment('Trạng thái giao hàng');
            $table->boolean('is_flash_sale')->default(false)->comment('Đơn có sản phẩm flash sale hay không');
            $table->text('customer_note')->nullable()->comment('Ghi chú từ khách hàng');
            $table->text('admin_note')->nullable()->comment('Ghi chú nội bộ');
            $table->foreignId('voucher_id')
                ->nullable()
                ->constrained('vouchers')
                ->nullOnDelete();
            $table->string('shipping_method')->nullable()->comment('Phương thức giao hàng');
            $table->string('status')->default('pending')->comment('Trạng thái đơn hàng');
            $table->string('note')->nullable()->comment('Ghi chú');
            $table->string('ip')->nullable()->comment('Địa chỉ IP đặt hàng');
            $table->timestamps();

            $table->index('account_id', 'orders_account_id_index');
            $table->index('session_id', 'orders_session_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
