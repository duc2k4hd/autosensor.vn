<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\SlugIndex;
use Illuminate\Support\Facades\Log;

class ProductSlugObserver
{
    public function saved(Product $product): void
    {
        $this->sync($product, true);
    }

    public function deleted(Product $product): void
    {
        $this->sync($product, false);
    }

    protected function sync(Product $product, bool $active): void
    {
        $slug = $product->slug;
        if (empty($slug)) {
            return;
        }

        try {
            SlugIndex::updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => 'product',
                    'entity_id' => $product->id,
                    'is_active' => $active && (bool) ($product->is_active ?? false),
                    'target_slug' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('ProductSlugObserver sync failed', [
                'product_id' => $product->id,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

