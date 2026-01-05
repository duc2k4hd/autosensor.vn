<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VouchersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vouchers')->upsert([
            [
                'id' => 1,
                'code' => 'SALE10',
                'name' => 'Giảm 10% đơn hàng',
                'description' => 'Giảm 10% cho tất cả sản phẩm, tối đa 50.000đ.',
                'image' => 'clients/assets/img/vouchers/1764642888_wQZpJWqiKl.webp',
                'account_id' => null,
                'updated_by' => null,
                'type' => 'percent',
                'value' => 10.00,
                'max_discount' => 50000.00,
                'min_order_value' => 0,
                'usage_limit' => 500,
                'usage_limit_per_user' => 1,
                'start_time' => '2025-01-01 00:00:00',
                'end_time' => '2025-12-31 23:59:00',
                'is_active' => 1,
                'apply_for' => null,
                'created_at' => '2025-11-25 09:50:46',
                'updated_at' => '2025-12-02 02:34:49',
            ],
            [
                'id' => 2,
                'code' => 'GIAM30K',
                'name' => 'Giảm 30.000đ',
                'description' => 'Giảm trực tiếp 30.000đ cho mọi đơn từ 199.000đ.',
                'image' => null,
                'account_id' => null,
                'updated_by' => null,
                'type' => 'fixed',
                'value' => 30000.00,
                'max_discount' => null,
                'min_order_value' => 199000,
                'usage_limit' => 300,
                'usage_limit_per_user' => 2,
                'start_time' => '2025-01-01 00:00:00',
                'end_time' => '2025-12-31 23:59:59',
                'is_active' => 1,
                'apply_for' => '{"type": "all"}',
                'created_at' => '2025-11-25 09:50:46',
                'updated_at' => '2025-11-25 09:50:46',
            ],
            [
                'id' => 3,
                'code' => 'FREESHIP25K',
                'name' => 'Miễn phí vận chuyển',
                'description' => 'Miễn phí vận chuyển tối đa 25.000đ cho đơn từ 99.000đ.',
                'image' => null,
                'account_id' => null,
                'updated_by' => null,
                'type' => 'free_shipping',
                'value' => 25000.00,
                'max_discount' => 25000.00,
                'min_order_value' => 99000,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 2,
                'start_time' => '2025-01-01 00:00:00',
                'end_time' => '2025-12-31 23:59:59',
                'is_active' => 1,
                'apply_for' => '{"type": "shipping"}',
                'created_at' => '2025-11-25 09:50:46',
                'updated_at' => '2025-11-25 09:50:46',
            ],
            [
                'id' => 4,
                'code' => 'AOSOMI15',
                'name' => 'Giảm 15% Áo sơ mi',
                'description' => 'Giảm 15% cho các sản phẩm thuộc danh mục Áo sơ mi.',
                'image' => null,
                'account_id' => null,
                'updated_by' => null,
                'type' => 'percent',
                'value' => 15.00,
                'max_discount' => 60000.00,
                'min_order_value' => 0,
                'usage_limit' => 200,
                'usage_limit_per_user' => 1,
                'start_time' => '2025-01-01 00:00:00',
                'end_time' => '2025-06-30 23:59:59',
                'is_active' => 0,
                'apply_for' => '{"type": "category", "category_ids": [10]}',
                'created_at' => '2025-11-25 09:50:46',
                'updated_at' => '2025-12-02 02:08:47',
            ],
        ], ['id']);
    }
}
