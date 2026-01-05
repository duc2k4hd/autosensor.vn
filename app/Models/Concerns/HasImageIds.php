<?php

namespace App\Models\Concerns;

use App\Models\Image;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

trait HasImageIds
{
    protected ?Collection $resolvedImages = null;

    protected static array $imagePool = [];

    /**
     * Normalize the current model image ids.
     */
    protected function normalizedImageIds(): array
    {
        $ids = $this->image_ids ?? [];

        if (! is_array($ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));

        return array_filter($ids, fn ($id) => $id > 0);
    }

    /**
     * Ensure all provided ids exist in the in-memory pool.
     */
    protected static function hydrateImagePool(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (empty($ids)) {
            return;
        }

        $missing = array_diff($ids, array_keys(static::$imagePool));

        if (empty($missing)) {
            return;
        }

        Image::whereIn('id', $missing)->get()->each(function (Image $image) {
            static::$imagePool[$image->id] = $image;
        });

        foreach ($missing as $id) {
            if (! array_key_exists($id, static::$imagePool)) {
                static::$imagePool[$id] = null;
            }
        }
    }

    /**
     * Preload images for a set of models to avoid N+1 queries.
     *
     * @param  \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|array|null  $models
     */
    public static function preloadImages($models): void
    {
        if (! $models) {
            return;
        }

        if (is_array($models)) {
            $models = new EloquentCollection($models);
        }

        if (! $models instanceof EloquentCollection) {
            $models = new EloquentCollection($models->all());
        }

        $ids = $models->flatMap(function ($model) {
            return $model->image_ids ?? [];
        })
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        static::hydrateImagePool($ids);
    }

    /**
     * @return \Illuminate\Support\Collection<int,\App\Models\Image>
     */
    public function getImagesAttribute(): Collection
    {
        if ($this->resolvedImages instanceof Collection) {
            return $this->resolvedImages;
        }

        $ids = $this->normalizedImageIds();

        if (empty($ids)) {
            return $this->resolvedImages = collect();
        }

        static::hydrateImagePool($ids);

        return $this->resolvedImages = collect($ids)
            ->map(fn ($id) => static::$imagePool[$id] ?? null)
            ->filter()
            ->values();
    }

    public function getPrimaryImageAttribute(): ?Image
    {
        return $this->images->first();
    }

    public function clearResolvedImages(): void
    {
        $this->resolvedImages = null;
    }
}
