<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action')->comment('create, update, delete, etc.');
            $table->string('model_type')->comment('Product, Order, Account, etc.');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Người thực hiện');
            $table->text('description')->nullable();
            $table->json('old_data')->nullable()->comment('Dữ liệu cũ (trước khi thay đổi)');
            $table->json('new_data')->nullable()->comment('Dữ liệu mới (sau khi thay đổi)');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id'], 'activity_logs_model_type_model_id_index');
            $table->index('account_id', 'activity_logs_account_id_index');
            $table->index('action', 'activity_logs_action_index');
            $table->index('created_at', 'activity_logs_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
