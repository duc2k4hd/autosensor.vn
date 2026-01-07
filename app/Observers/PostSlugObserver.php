<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\SlugIndex;
use Illuminate\Support\Facades\Log;

class PostSlugObserver
{
    public function saved(Post $post): void
    {
        $this->sync($post, true);
    }

    public function deleted(Post $post): void
    {
        $this->sync($post, false);
    }

    protected function sync(Post $post, bool $active): void
    {
        $slug = $post->slug;
        if (empty($slug)) {
            return;
        }

        $isPublished = ($post->status === 'published');

        try {
            SlugIndex::updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => 'post',
                    'entity_id' => $post->id,
                    'is_active' => $active && $isPublished,
                    'target_slug' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('PostSlugObserver sync failed', [
                'post_id' => $post->id,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

