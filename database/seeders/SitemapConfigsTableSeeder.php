<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SitemapConfigsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sitemap_configs')->upsert([
            ['id' => 1, 'config_key' => 'enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'config_key' => 'posts_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'config_key' => 'products_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'config_key' => 'categories_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'config_key' => 'tags_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'config_key' => 'pages_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 7, 'config_key' => 'images_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => '2025-12-02 08:26:20'],
            ['id' => 8, 'config_key' => 'ping_google_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 9, 'config_key' => 'ping_bing_enabled', 'config_value' => '1', 'value_type' => 'boolean', 'created_at' => null, 'updated_at' => null],
            ['id' => 10, 'config_key' => 'urls_per_file', 'config_value' => '10000', 'value_type' => 'integer', 'created_at' => null, 'updated_at' => null],
            ['id' => 11, 'config_key' => 'last_generated_at', 'config_value' => '2025-12-02 15:26:05', 'value_type' => 'datetime', 'created_at' => null, 'updated_at' => '2025-12-02 08:26:05'],
        ], ['id']);
    }
}
