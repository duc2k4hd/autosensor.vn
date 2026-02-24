<?php

namespace App\Helpers;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryHelper
{
    /**
     * Generate unique slug for category (globally unique, not just within parent level)
     */
    public static function generateUniqueSlugGlobal(string $baseSlug, ?int $excludeId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (self::slugExistsGlobal($slug, $excludeId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate unique slug for category (legacy method - checks within parent level)
     *
     * @deprecated Use generateUniqueSlugGlobal instead
     */
    public static function generateUniqueSlug(string $name, ?int $parentId = null, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);

        return self::generateUniqueSlugGlobal($baseSlug, $excludeId);
    }

    /**
     * Check if slug exists globally (not just within parent level)
     */
    public static function slugExistsGlobal(string $slug, ?int $excludeId = null): bool
    {
        $query = Category::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if slug exists in same parent level
     */
    public static function slugExists(string $slug, ?int $parentId, ?int $excludeId = null): bool
    {
        $query = Category::where('slug', $slug)
            ->where('parent_id', $parentId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Build category tree structure.
     *
     * Sử dụng 1 query duy nhất để load tất cả categories,
     * sau đó build tree trong bộ nhớ — tránh N+1 query.
     */
    public static function buildTree(?int $parentId = null, bool $includeInactive = false): array
    {
        // Load toàn bộ categories 1 lần
        $query = Category::query()->orderBy('order')->orderBy('name');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        $all = $query->get()->keyBy('id');

        // Group nhanh theo parent_id
        $byParent = [];
        foreach ($all as $cat) {
            $byParent[$cat->parent_id ?? 0][] = $cat;
        }

        // Đếm chính xác số con dựa trên collection đã load
        foreach ($all as $cat) {
            $cat->_children_count = count($byParent[$cat->id] ?? []);
        }

        // Build tree đệ quy trong bộ nhớ (không query DB thêm)
        $buildNode = function ($pid) use (&$buildNode, &$byParent) {
            $nodes = $byParent[$pid ?? 0] ?? [];
            $result = [];

            foreach ($nodes as $category) {
                $result[] = [
                    'id'             => $category->id,
                    'name'           => $category->name,
                    'slug'           => $category->slug,
                    'parent_id'      => $category->parent_id,
                    'image'          => $category->image,
                    'order'          => $category->order,
                    'is_active'      => $category->is_active,
                    'children_count' => $category->_children_count,
                    'children'       => $buildNode($category->id),
                ];
            }

            return $result;
        };

        return $buildNode($parentId);
    }


    /**
     * Get all descendants of a category (including itself).
     *
     * Load toàn bộ parent_id map 1 lần rồi duyệt trong memory.
     * Static cache tránh query lại trong cùng request.
     */
    public static function getDescendants(int $categoryId): array
    {
        // Static cache cho toàn bộ parent → children map (1 query/request)
        static $childrenMap = null;

        if ($childrenMap === null) {
            $childrenMap = [];
            Category::query()
                ->select('id', 'parent_id')
                ->get()
                ->each(function ($cat) use (&$childrenMap) {
                    $childrenMap[$cat->parent_id ?? 0][] = $cat->id;
                });
        }

        // BFS trong memory — không query DB thêm
        $result = [$categoryId];
        $queue  = [$categoryId];

        while (! empty($queue)) {
            $pid      = array_shift($queue);
            $children = $childrenMap[$pid] ?? [];
            foreach ($children as $childId) {
                $result[] = $childId;
                $queue[]  = $childId;
            }
        }

        return array_values(array_unique($result));

    }

    /**

     * Check if category can be moved to new parent (prevent circular reference)
     */
    public static function canMoveToParent(int $categoryId, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return true;
        }

        // Cannot move to itself
        if ($categoryId === $newParentId) {
            return false;
        }

        // Cannot move to its own descendant
        $descendants = self::getDescendants($categoryId);

        return ! in_array($newParentId, $descendants);
    }

    /**
     * Get breadcrumb path for category
     */
    public static function getBreadcrumb(Category $category): array
    {
        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);

            $current = $current->parent;
        }

        return $breadcrumb;
    }

    /**
     * Get category path string (e.g., "Parent > Child > Grandchild")
     */
    public static function getPathString(Category $category): string
    {
        $breadcrumb = self::getBreadcrumb($category);

        return implode(' > ', array_column($breadcrumb, 'name'));
    }

    /**
     * Get all categories for dropdown (flat list with indentation).
     *
     * Sử dụng 1 query duy nhất thay vì đệ quy query từng level — tránh N+1.
     */
    public static function getDropdownOptions(?int $excludeId = null, ?int $parentId = null, int $level = 0): array
    {
        // Nếu gọi đệ quy từ code cũ với $parentId/$level đã truyền vào,
        // chỉ xử lý cho lần gọi gốc (level=0, parentId=null)
        // để tránh query thêm. Các lần gọi đệ quy sẽ không bao giờ được dùng.
        static $cache = [];
        $cacheKey = ($excludeId ?? 0);

        if (! isset($cache[$cacheKey])) {
            // Load toàn bộ 1 lần
            $all = Category::query()
                ->orderBy('order')
                ->orderBy('name')
                ->get();

            // Group theo parent_id
            $byParent = [];
            foreach ($all as $cat) {
                if ($excludeId && $cat->id === $excludeId) {
                    continue;
                }
                $byParent[$cat->parent_id ?? 0][] = $cat;
            }

            // Build danh sách phẳng có thụt lề trong bộ nhớ
            $flatten = function (int $pid, int $lvl) use (&$flatten, &$byParent): array {
                $nodes = $byParent[$pid] ?? [];
                $result = [];
                foreach ($nodes as $category) {
                    $prefix = str_repeat('— ', $lvl);
                    $statusIcon = $category->is_active ? '✅' : '❌';
                    $result[] = [
                        'value'    => $category->id,
                        'label'    => $prefix.$statusIcon.' '.$category->name,
                        'category' => $category,
                    ];
                    $result = array_merge($result, $flatten($category->id, $lvl + 1));
                }
                return $result;
            };

            $cache[$cacheKey] = $flatten(0, 0);
        }

        // Nếu được gọi với $parentId cụ thể (đệ quy từ code cũ), trả cache gốc
        return $cache[$cacheKey];
    }

}
