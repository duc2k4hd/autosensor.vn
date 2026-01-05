<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('banners')->upsert([
            [
                'id' => 1,
                'title' => 'Banner 1',
                'description' => null,
                'image_desktop' => 'banner-nobifashion.vn.png',
                'image_mobile' => null,
                'link' => null,
                'target' => '_blank',
                'position' => 'homepage_banner_parent',
                'order' => 0,
                'start_at' => null,
                'end_at' => null,
                'is_active' => 1,
                'created_at' => '2025-11-25 07:55:45',
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'title' => 'Banner 2',
                'description' => null,
                'image_desktop' => 'Banner-trang-chu-nobifashion.png',
                'image_mobile' => null,
                'link' => null,
                'target' => '_blank',
                'position' => 'homepage_banner_parent',
                'order' => 1,
                'start_at' => null,
                'end_at' => null,
                'is_active' => 1,
                'created_at' => '2025-11-25 07:55:45',
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'title' => 'Banner 3',
                'description' => null,
                'image_desktop' => 'banner-trang-home.png',
                'image_mobile' => null,
                'link' => null,
                'target' => '_blank',
                'position' => 'homepage_banner_parent',
                'order' => 2,
                'start_at' => null,
                'end_at' => null,
                'is_active' => 1,
                'created_at' => '2025-11-25 07:57:27',
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 4,
                'title' => 'Banner con 1',
                'description' => null,
                'image_desktop' => 'banner-con-trang-chu.png',
                'image_mobile' => null,
                'link' => null,
                'target' => '_blank',
                'position' => 'homepage_banner_children',
                'order' => 0,
                'start_at' => null,
                'end_at' => null,
                'is_active' => 1,
                'created_at' => '2025-11-25 09:22:00',
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'id' => 5,
                'title' => 'Banner con 2',
                'description' => null,
                'image_desktop' => 'banner-con-trang-home-nobifashion.png',
                'image_mobile' => null,
                'link' => null,
                'target' => '_blank',
                'position' => 'homepage_banner_children',
                'order' => 1,
                'start_at' => null,
                'end_at' => null,
                'is_active' => 1,
                'created_at' => '2025-11-25 09:22:00',
                'updated_at' => null,
                'deleted_at' => null,
            ],
        ], ['id']);
    }
}
