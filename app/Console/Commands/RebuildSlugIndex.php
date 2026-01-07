<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\SlugIndex;
use Illuminate\Console\Command;

class RebuildSlugIndex extends Command
{
    protected $signature = 'slug-index:rebuild {--force-clear : Xóa toàn bộ slug_indexes trước khi build lại}';

    protected $description = 'Rebuild lại bảng slug_indexes từ products, posts, categories (chạy một lần hoặc khi cần đồng bộ lại)';

    public function handle(): int
    {
        $this->info('🔁 Bắt đầu rebuild slug_indexes...');

        if ($this->option('force-clear')) {
            $this->warn('⚠ Xóa toàn bộ slug_indexes hiện tại...');
            SlugIndex::truncate();
        }

        $totalInserted = 0;

        // Rebuild cho Products
        $this->info('➡ Đang xử lý products...');
        Product::query()
            ->select('id', 'slug', 'is_active')
            ->whereNotNull('slug')
            ->orderBy('id')
            ->chunkById(500, function ($products) use (&$totalInserted) {
                foreach ($products as $product) {
                    if (! $product->slug) {
                        continue;
                    }

                    SlugIndex::updateOrCreate(
                        ['slug' => $product->slug],
                        [
                            'type' => 'product',
                            'entity_id' => $product->id,
                            'is_active' => (bool) $product->is_active,
                            'target_slug' => null,
                        ]
                    );

                    $totalInserted++;
                }

                $this->line('   + Đã xử lý thêm '.count($products).' products...');
            });

        // Rebuild cho Posts
        $this->info('➡ Đang xử lý posts...');
        Post::query()
            ->select('id', 'slug', 'status')
            ->whereNotNull('slug')
            ->orderBy('id')
            ->chunkById(500, function ($posts) use (&$totalInserted) {
                foreach ($posts as $post) {
                    if (! $post->slug) {
                        continue;
                    }

                    $isPublished = ($post->status === 'published');

                    SlugIndex::updateOrCreate(
                        ['slug' => $post->slug],
                        [
                            'type' => 'post',
                            'entity_id' => $post->id,
                            'is_active' => $isPublished,
                            'target_slug' => null,
                        ]
                    );

                    $totalInserted++;
                }

                $this->line('   + Đã xử lý thêm '.count($posts).' posts...');
            });

        // Rebuild cho Categories
        $this->info('➡ Đang xử lý categories...');
        Category::query()
            ->select('id', 'slug', 'is_active')
            ->whereNotNull('slug')
            ->orderBy('id')
            ->chunkById(500, function ($categories) use (&$totalInserted) {
                foreach ($categories as $category) {
                    if (! $category->slug) {
                        continue;
                    }

                    SlugIndex::updateOrCreate(
                        ['slug' => $category->slug],
                        [
                            'type' => 'category',
                            'entity_id' => $category->id,
                            'is_active' => (bool) $category->is_active,
                            'target_slug' => null,
                        ]
                    );

                    $totalInserted++;
                }

                $this->line('   + Đã xử lý thêm '.count($categories).' categories...');
            });

        $this->info('✅ Hoàn thành rebuild slug_indexes. Tổng bản ghi đã sync/insert: '.$totalInserted);

        return Command::SUCCESS;
    }
}


