<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id()->comment('Khóa chính liên hệ');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Tài khoản liên quan (nếu có)');
            $table->string('name')->nullable()->comment('Tên người gửi');
            $table->string('email')->nullable()->comment('Email người gửi');
            $table->string('phone')->nullable()->comment('Số điện thoại');
            $table->string('subject')->nullable()->comment('Tiêu đề');
            $table->text('message')->nullable()->comment('Nội dung liên hệ');
            $table->text('attachment_path')->nullable()->comment('Đường dẫn tệp đính kèm của khách hàng');
            $table->string('ip')->nullable()->comment('Địa chỉ IP');
            $table->string('status', 30)->default('new')->comment('Trạng thái xử lý: new, processing, done, spam');
            $table->string('source', 100)->nullable()->comment('Nguồn liên hệ: contact_form, landing_page, popup, ...');
            $table->text('admin_note')->nullable()->comment('Ghi chú nội bộ của admin');
            $table->timestamp('last_replied_at')->nullable()->comment('Thời gian trả lời cuối cùng');
            $table->unsignedInteger('reply_count')->default(0)->comment('Số lần đã trả lời khách');
            $table->boolean('is_read')->default(false)->comment('Đã đọc hay chưa');
            $table->timestamp('created_at')->nullable()->comment('Thời điểm gửi');
            $table->timestamp('updated_at')->nullable()->comment('Thời điểm cập nhật');
            $table->softDeletes();

            $table->index('status', 'contacts_status_index');
            $table->index('source', 'contacts_source_index');
            $table->index('is_read', 'contacts_is_read_index');
            $table->index('created_at', 'contacts_created_at_index');
        });

        Schema::create('contact_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete()
                ->comment('Liên hệ gốc');
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('Admin gửi phản hồi');
            $table->longText('message')->comment('Nội dung trả lời (HTML)');
            $table->timestamps();

            $table->index('contact_id', 'contact_replies_contact_id_index');
            $table->index('account_id', 'contact_replies_account_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_replies');
        Schema::dropIfExists('contacts');
    }
};
