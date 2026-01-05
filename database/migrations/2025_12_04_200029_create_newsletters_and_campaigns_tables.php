<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id()->comment('Khóa chính đăng ký nhận tin');
            $table->string('email')->comment('Email đăng ký');
            $table->string('ip')->nullable()->comment('IP đăng ký');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP đăng ký (chuẩn hóa)');
            $table->text('user_agent')->nullable()->comment('User agent khi đăng ký');
            $table->text('note')->nullable()->comment('Ghi chú nội bộ admin');
            $table->boolean('is_verified')->default(false)->comment('Đã xác thực?');
            $table->string('status', 30)->default('pending')->comment('Trạng thái: pending, subscribed, unsubscribed');
            $table->string('source', 100)->nullable()->comment('Nguồn đăng ký: homepage_form, popup, checkout, ...');
            $table->string('verify_token', 100)->nullable()->comment('Token xác nhận / hủy đăng ký');
            $table->timestamp('verified_at')->nullable()->comment('Thời gian xác nhận đăng ký');
            $table->timestamp('unsubscribed_at')->nullable()->comment('Thời gian hủy đăng ký');
            $table->timestamps();

            $table->unique('email', 'newsletters_email_unique');
            $table->unique('verify_token', 'newsletters_verify_token_unique');
            $table->index('status', 'newsletters_status_index');
            $table->index('source', 'newsletters_source_index');
            $table->index('created_at', 'newsletters_created_at_index');
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('subject');
            $table->longText('content')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('cta_text')->nullable();
            $table->longText('footer')->nullable();
            $table->string('filter_status')->nullable();
            $table->string('filter_source')->nullable();
            $table->date('filter_date_from')->nullable();
            $table->date('filter_date_to')->nullable();
            $table->unsignedInteger('total_target')->default(0);
            $table->unsignedInteger('sent_success')->default(0);
            $table->unsignedInteger('sent_failed')->default(0);
            $table->string('status', 50)->default('completed');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('created_by', 'newsletter_campaigns_created_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletters');
    }
};
