<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id()->comment('Khóa chính hồ sơ tài khoản');
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->comment('ID tài khoản');
            $table->string('fullname')->nullable()->comment('Họ và tên');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->string('avatar')->nullable()->comment('Ảnh đại diện');
            $table->string('gender', 10)->nullable()->comment('Giới tính');
            $table->date('birthday')->nullable()->comment('Ngày sinh');
            $table->json('extra')->nullable()->comment('Dữ liệu mở rộng (JSON)');
            $table->timestamps();

            $table->unique('account_id', 'profiles_account_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
