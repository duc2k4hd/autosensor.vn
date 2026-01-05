<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Str;

class TagService
{
    /**
     * Tạo tag mới
     */
    public function create(array $data): Tag
    {
        // Normalize entity_type để đảm bảo luôn lưu class name
        if (isset($data['entity_type'])) {
            $data['entity_type'] = $this->normalizeEntityType($data['entity_type']);
        }

        // Generate slug nếu không có
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Set defaults
        $data['is_active'] = $data['is_active'] ?? true;
        $data['usage_count'] = 0;

        $tag = Tag::create($data);

        return $tag;
    }

    /**
     * Cập nhật tag
     */
    public function update(Tag $tag, array $data): Tag
    {
        // Normalize entity_type để đảm bảo luôn lưu class name
        if (isset($data['entity_type'])) {
            $data['entity_type'] = $this->normalizeEntityType($data['entity_type']);
        }

        // Generate slug nếu name thay đổi và slug không được cung cấp
        if (isset($data['name']) && $data['name'] !== $tag->name) {
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name'], $tag->id);
            }
        }

        $tag->update($data);

        return $tag->fresh();
    }

    /**
     * Xóa tag
     */
    public function delete(Tag $tag): bool
    {
        if ($tag->usage_count > 0) {
            throw new \Exception('Không thể xóa tag đang được sử dụng.');
        }

        return $tag->delete();
    }

    /**
     * Xóa nhiều tags
     */
    public function deleteMultiple(array $ids): int
    {
        $tags = Tag::whereIn('id', $ids)
            ->where('usage_count', 0)
            ->get();

        $deleted = 0;
        foreach ($tags as $tag) {
            if ($tag->delete()) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Gộp tags (source vào target)
     */
    public function merge(Tag $source, Tag $target): Tag
    {
        if ($source->id === $target->id) {
            throw new \Exception('Không thể gộp tag với chính nó.');
        }

        if ($source->entity_type !== $target->entity_type || $source->entity_id !== $target->entity_id) {
            throw new \Exception('Chỉ có thể gộp tags của cùng một entity.');
        }

        // Cập nhật usage_count
        $target->usage_count += $source->usage_count;
        $target->save();

        // Xóa source tag
        $source->delete();

        return $target;
    }

    /**
     * Gợi ý tags theo keyword
     */
    public function suggest(string $keyword, ?string $entityType = null, int $limit = 10): array
    {
        $query = Tag::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('slug', 'like', "%{$keyword}%");

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        return $query->limit($limit)
            ->get(['id', 'name', 'slug'])
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ];
            })
            ->toArray();
    }

    /**
     * Gợi ý tags từ content
     */
    public function suggestFromContent(string $content, ?string $entityType = null, int $limit = 5): array
    {
        // Extract keywords từ content (đơn giản: lấy các từ có độ dài > 3)
        $words = preg_split('/\s+/', $content);
        $keywords = array_filter($words, fn ($word) => mb_strlen(trim($word)) > 3);

        if (empty($keywords)) {
            return [];
        }

        $suggestions = [];
        foreach (array_slice($keywords, 0, $limit) as $keyword) {
            $keyword = trim($keyword);
            $tags = $this->suggest($keyword, $entityType, 1);
            if (! empty($tags)) {
                $suggestions = array_merge($suggestions, $tags);
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Generate unique slug từ name
     */
    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Tag::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Tăng usage_count
     */
    public function incrementUsageCount(Tag $tag): void
    {
        $tag->increment('usage_count');
    }

    /**
     * Giảm usage_count
     */
    public function decrementUsageCount(Tag $tag): void
    {
        if ($tag->usage_count > 0) {
            $tag->decrement('usage_count');
        }
    }

    /**
     * Normalize entity_type từ short name sang class name
     */
    private function normalizeEntityType(string $type): string
    {
        return match ($type) {
            'product' => \App\Models\Product::class,
            'post' => \App\Models\Post::class,
            default => $type, // Nếu đã là class name thì giữ nguyên
        };
    }
}
