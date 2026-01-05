<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id()->comment('Khóa chính địa chỉ');
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->comment('ID tài khoản');
            $table->string('full_name')->comment('Họ tên người nhận');
            $table->string('phone_number')->comment('SĐT');
            $table->string('detail_address')->comment('Địa chỉ chi tiết');
            $table->string('ward')->nullable()->comment('Phường');
            $table->string('district')->comment('Quận');
            $table->string('province')->comment('Tỉnh');
            $table->unsignedBigInteger('province_code')->nullable()->comment('Mã tỉnh');
            $table->unsignedBigInteger('district_code')->nullable()->comment('Mã huyện');
            $table->string('ward_code')->nullable()->comment('Mã phường');
            $table->string('postal_code')->comment('Mã bưu chính');
            $table->string('country')->comment('Quốc gia');
            $table->string('latitude')->nullable()->comment('Vĩ độ');
            $table->string('longitude')->nullable()->comment('Kinh độ');
            $table->string('address_type')->default('home')->comment('Loại địa chỉ');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->boolean('is_default')->default(false)->comment('Địa chỉ mặc định');
            $table->timestamps();

            $table->index('account_id', 'addresses_account_id_index');
        });

        Schema::create('address_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_id')
                ->constrained('addresses')
                ->cascadeOnDelete()
                ->comment('ID địa chỉ');
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->comment('ID người thực hiện');
            $table->string('action')->comment('Hành động thực hiện');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->json('changes')->nullable()->comment('Các thay đổi');
            $table->timestamps();

            $table->index('address_id', 'address_audits_address_id_index');
            $table->index('performed_by', 'address_audits_performed_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_audits');
        Schema::dropIfExists('addresses');
    }
};
