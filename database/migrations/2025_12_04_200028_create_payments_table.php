<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->enum('method', ['cod', 'bank_transfer', 'qr', 'momo', 'zalopay', 'vnpay', 'credit_card', 'payos']);
            $table->decimal('amount', 10, 2);
            $table->string('gateway')->nullable();
            $table->string('transaction_code')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('last_four')->nullable();
            $table->string('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('order_id', 'payments_order_id_foreign');
            $table->index('account_id', 'payments_account_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
