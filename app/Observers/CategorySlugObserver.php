<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\SlugIndex;
use Illuminate\Support\Facades\Log;

class CategorySlugObserver
{
    public function saved(Category $category): void
    {
        $this->sync($category, true);
    }

    public function deleted(Category $category): void
    {
        $this->sync($category, false);
    }

    protected function sync(Category $category, bool $active): void
    {
        $slug = $category->slug;
        if (empty($slug)) {
            return;
        }

        try {
            SlugIndex::updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => 'category',
                    'entity_id' => $category->id,
                    'is_active' => $active && (bool) ($category->is_active ?? false),
                    'target_slug' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CategorySlugObserver sync failed', [
                'category_id' => $category->id,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

