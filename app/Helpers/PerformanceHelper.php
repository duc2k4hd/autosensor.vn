<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceHelper
{
    /**
     * Cache query result with automatic invalidation
     */
    public static function rememberQuery(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return Cache::remember($key, $ttl, function () use ($callback) {
            DB::enableQueryLog();
            $result = $callback();
            $queries = DB::getQueryLog();

            // Log slow queries
            foreach ($queries as $query) {
                if (($query['time'] ?? 0) > 1000) { // > 1 second
                    \Log::warning('Slow query detected', [
                        'query' => $query['query'],
                        'bindings' => $query['bindings'] ?? [],
                        'time' => $query['time'],
                    ]);
                }
            }

            DB::disableQueryLog();

            return $result;
        });
    }

    /**
     * Preload relationships to avoid N+1 queries
     */
    public static function preloadRelations($models, array $relations): void
    {
        if ($models instanceof \Illuminate\Database\Eloquent\Collection) {
            $models->loadMissing($relations);
        } elseif (is_array($models)) {
            foreach ($models as $model) {
                if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                    $model->loadMissing($relations);
                }
            }
        }
    }

    /**
     * Get pagination cache key
     */
    public static function getPaginationCacheKey(string $baseKey, int $page, array $filters = []): string
    {
        $filterHash = md5(json_encode($filters));

        return "{$baseKey}_page_{$page}_{$filterHash}";
    }

    /**
     * Batch load models to avoid N+1
     */
    public static function batchLoad(array $ids, string $modelClass, array $relations = []): \Illuminate\Database\Eloquent\Collection
    {
        $models = $modelClass::whereIn('id', $ids)->get();

        if (! empty($relations)) {
            $models->load($relations);
        }

        return $models;
    }
}
