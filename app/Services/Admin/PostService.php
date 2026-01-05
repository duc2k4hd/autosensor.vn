<?php

namespace App\Services\Admin;

use App\Models\Account;
use App\Models\Image;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Tạo bài viết mới
     */
    public function create(array $data, Account $user): Post
    {
        return DB::transaction(function () use ($data, $user) {
            // Tạo slug nếu chưa có
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title']);
            } else {
                $data['slug'] = $this->generateUniqueSlug($data['slug']);
            }

            // Nếu để trống canonical URL thì tự set theo slug
            if (empty($data['meta_canonical'])) {
                $data['meta_canonical'] = '/kinh-nghiem/'.$data['slug'];
            }

            // Set created_by và account_id
            $data['created_by'] = $user->id;
            if (empty($data['account_id'])) {
                $data['account_id'] = $user->id;
            }

            // Set published_at nếu status là published
            if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            // Lưu tag_ids và tag_names để sync sau
            $tagIds = Arr::get($data, 'tag_ids', []);
            $tagNames = Arr::get($data, 'tag_names');
            unset($data['tag_ids'], $data['tag_names']);

            // Lưu image_ids để sync sau
            $imageIds = Arr::get($data, 'image_ids', []);
            unset($data['image_ids']);

            // Xử lý meta_keywords: convert empty string thành null
            if (isset($data['meta_keywords']) && $data['meta_keywords'] === '') {
                $data['meta_keywords'] = null;
            }

            $post = Post::create($data);

            // Sync images
            $this->syncImages($post, $imageIds);

            // Sync tags với bảng tags (sẽ tạo tags mới và cập nhật tag_ids)
            $this->syncTags($post, $tagIds, $tagNames);

            return $post->fresh();
        });
    }

    /**
     * Cập nhật bài viết
     */
    public function update(Post $post, array $data, Account $user): Post
    {
        return DB::transaction(function () use ($post, $data) {
            // Update slug nếu thay đổi title hoặc slug
            if (isset($data['title']) && $data['title'] !== $post->title) {
                if (empty($data['slug']) || $data['slug'] === $post->slug) {
                    $data['slug'] = $this->generateUniqueSlug($data['title'], $post->id);
                }
            } elseif (isset($data['slug']) && $data['slug'] !== $post->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $post->id);
            }

            // Nếu meta_canonical trống sau khi xử lý slug thì tự set lại
            if (empty($data['meta_canonical'])) {
                $data['meta_canonical'] = '/kinh-nghiem/'.($data['slug'] ?? $post->slug);
            }

            // Lưu tag_ids và tag_names để sync sau
            // QUAN TRỌNG: Chỉ sync tags nếu có thay đổi thực sự
            // Form HTML luôn gửi tag_ids[] ngay cả khi không chọn, nên cần kiểm tra cẩn thận
            $tagIds = $post->tag_ids ?? [];
            $tagNames = null;
            $shouldSyncTags = false;

            // Kiểm tra xem tag_ids và tag_names có được gửi trong request không
            // Lưu ý: tag_names có thể được gửi nhưng rỗng, nên cần kiểm tra giá trị
            $hasTagIds = array_key_exists('tag_ids', $data);
            $tagNamesValue = $data['tag_names'] ?? null;
            $hasTagNames = array_key_exists('tag_names', $data);
            $hasValidTagNames = $hasTagNames && ! empty(trim($tagNamesValue ?? ''));

            // Nếu có tag_names hợp lệ, luôn sync (vì có thể có tags mới)
            if ($hasValidTagNames) {
                $tagNames = $data['tag_names'];
                $shouldSyncTags = true;

                // QUAN TRỌNG: Merge tag_ids từ form với tag_ids hiện tại từ post
                // để đảm bảo không mất tags cũ khi thêm tags mới
                $currentTagIds = is_array($post->tag_ids)
                    ? array_values(array_filter(array_map('intval', $post->tag_ids)))
                    : [];

                $formTagIds = [];
                if ($hasTagIds && is_array($data['tag_ids'])) {
                    $formTagIds = array_values(array_unique(array_filter(array_map('intval', $data['tag_ids']))));
                }

                // Merge: lấy cả tag_ids từ form và tag_ids hiện tại
                $tagIds = array_values(array_unique(array_merge($currentTagIds, $formTagIds)));

                Log::debug('PostService::update - Adding new tags via tag_names', [
                    'post_id' => $post->id,
                    'tag_names' => $tagNames,
                    'current_tag_ids' => $post->tag_ids,
                    'current_tag_ids_int' => $currentTagIds,
                    'form_tag_ids' => $data['tag_ids'] ?? null,
                    'form_tag_ids_int' => $formTagIds,
                    'merged_tag_ids' => $tagIds,
                ]);
            } elseif ($hasTagIds) {
                // Chỉ có tag_ids, không có tag_names
                // Convert về integer và loại bỏ duplicate để so sánh chính xác
                $newTagIds = is_array($data['tag_ids'])
                    ? array_values(array_unique(array_filter(array_map('intval', $data['tag_ids']))))
                    : [];
                $currentTagIds = is_array($post->tag_ids)
                    ? array_values(array_filter(array_map('intval', $post->tag_ids)))
                    : [];

                // So sánh mảng (không quan tâm thứ tự)
                sort($newTagIds);
                sort($currentTagIds);

                // Chỉ sync nếu thực sự khác nhau
                if ($newTagIds !== $currentTagIds) {
                    // Nếu tag_ids rỗng, KHÔNG sync (giữ nguyên tags)
                    if (empty($newTagIds)) {
                        $shouldSyncTags = false;
                        $tagIds = $post->tag_ids ?? [];
                    } else {
                        // Kiểm tra xem new_tag_ids có phải là subset của current_tag_ids không
                        $newTagsSet = array_flip($newTagIds);
                        $currentTagsSet = array_flip($currentTagIds);
                        $hasNewTags = ! empty(array_diff_key($newTagsSet, $currentTagsSet));
                        $hasRemovedTags = ! empty(array_diff_key($currentTagsSet, $newTagsSet));

                        // Nếu chỉ có tags bị xóa (thiếu) mà không có tags mới
                        // Có thể là lỗi form không gửi đủ, nên không sync để tránh mất tags
                        if ($hasRemovedTags && ! $hasNewTags) {
                            $shouldSyncTags = false;
                            $tagIds = $post->tag_ids ?? [];
                        } else {
                            // Có thay đổi thực sự: có tag mới
                            $tagIds = $newTagIds;
                            $shouldSyncTags = true;
                        }
                    }
                } else {
                    $shouldSyncTags = false;
                    $tagIds = $post->tag_ids ?? [];
                }
            } else {
                // Không có tag_ids và tag_names, KHÔNG sync (giữ nguyên tags)
                $shouldSyncTags = false;
                $tagIds = $post->tag_ids ?? [];
            }

            unset($data['tag_ids'], $data['tag_names']);

            // Lưu image_ids để sync sau
            $imageIds = Arr::get($data, 'image_ids', []);
            $shouldSyncImages = isset($data['image_ids']);
            unset($data['image_ids']);

            // Xử lý meta_keywords: convert empty string thành null
            if (isset($data['meta_keywords']) && $data['meta_keywords'] === '') {
                $data['meta_keywords'] = null;
            }

            // Set published_at nếu status chuyển sang published
            if (($data['status'] ?? $post->status) === 'published' && empty($data['published_at']) && ! $post->published_at) {
                $data['published_at'] = now();
            }

            $post->update($data);

            // Sync images nếu có thay đổi
            if ($shouldSyncImages) {
                $this->syncImages($post, $imageIds);
            }

            // Sync tags CHỈ KHI có thay đổi thực sự
            // Bảo vệ: không sync nếu tag_ids rỗng và không có tag_names hợp lệ
            if ($shouldSyncTags) {
                $hasValidTagIds = ! empty($tagIds);
                $hasValidTagNames = ! empty($tagNames) && is_string($tagNames) && trim($tagNames) !== '';

                // Chỉ sync nếu có ít nhất một trong hai: tag_ids hoặc tag_names hợp lệ
                if ($hasValidTagIds || $hasValidTagNames) {
                    try {
                        $this->syncTags($post, $tagIds, $tagNames);
                    } catch (\Exception $e) {
                        Log::error('PostService::update - Error syncing tags', [
                            'post_id' => $post->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        throw $e;
                    }
                }
            }

            return $post->fresh();
        });
    }

    /**
     * Xóa bài viết (soft delete)
     */
    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    /**
     * Nhân bản bài viết
     */
    public function duplicate(Post $post, Account $user): Post
    {
        return DB::transaction(function () use ($post, $user) {
            $newPost = $post->replicate();
            $newPost->title = $post->title.' (Bản sao)';
            $newPost->slug = $this->generateUniqueSlug($post->slug);
            $newPost->status = 'draft';
            $newPost->published_at = null;
            $newPost->views = 0;
            $newPost->created_by = $user->id;
            $newPost->account_id = $user->id;
            $newPost->save();

            // Sync tags
            if ($post->tag_ids) {
                $this->syncTags($newPost, $post->tag_ids, null);
            }

            return $newPost;
        });
    }

    /**
     * Autosave revision
     */
    public function autosave(Post $post, array $data, Account $user): PostRevision
    {
        // Xóa autosave cũ của post này
        PostRevision::where('post_id', $post->id)
            ->where('is_autosave', true)
            ->delete();

        return PostRevision::create([
            'post_id' => $post->id,
            'edited_by' => $user->id,
            'title' => $data['title'] ?? $post->title,
            'content' => $data['content'] ?? $post->content,
            'excerpt' => $data['excerpt'] ?? $post->excerpt,
            'meta' => [
                'tag_ids' => $data['tag_ids'] ?? $post->tag_ids,
                'category_id' => $data['category_id'] ?? $post->category_id,
                'meta_title' => $data['meta_title'] ?? $post->meta_title,
                'meta_description' => $data['meta_description'] ?? $post->meta_description,
            ],
            'is_autosave' => true,
        ]);
    }

    /**
     * Khôi phục từ revision
     */
    public function restoreRevision(Post $post, PostRevision $revision, Account $user): Post
    {
        return DB::transaction(function () use ($post, $revision, $user) {
            $data = [
                'title' => $revision->title ?? $post->title,
                'content' => $revision->content ?? $post->content,
                'excerpt' => $revision->excerpt ?? $post->excerpt,
            ];

            if ($revision->meta) {
                $meta = $revision->meta;
                if (isset($meta['tag_ids'])) {
                    $data['tag_ids'] = $meta['tag_ids'];
                }
                if (isset($meta['category_id'])) {
                    $data['category_id'] = $meta['category_id'];
                }
                if (isset($meta['meta_title'])) {
                    $data['meta_title'] = $meta['meta_title'];
                }
                if (isset($meta['meta_description'])) {
                    $data['meta_description'] = $meta['meta_description'];
                }
            }

            return $this->update($post, $data, $user);
        });
    }

    /**
     * Tạo slug unique
     */
    protected function generateUniqueSlug(string $text, ?int $excludeId = null): string
    {
        $slug = Str::slug($text);
        $baseSlug = $slug;
        $counter = 1;

        while (Post::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Sync tags với bảng tags
     * Tái sử dụng tags đã có nếu cùng name, không tạo tag trùng lặp
     */
    protected function syncTags(Post $post, array $tagIds, ?string $tagNames = null): void
    {
        try {
            // Đảm bảo tagIds là integer array
            $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

            Log::debug('PostService::syncTags - Starting', [
                'post_id' => $post->id,
                'tag_ids_input' => $tagIds,
                'tag_names_input' => $tagNames,
                'current_post_tag_ids' => $post->tag_ids,
                'current_post_tag_ids_type' => gettype($post->tag_ids),
            ]);

            // QUAN TRỌNG: KHÔNG XÓA TAGS CŨ
            // Chỉ cập nhật post->tag_ids (JSON column) để lưu danh sách tag IDs
            // Tags trong database không cần có entity_id = post->id

            // Lấy thông tin tags từ tagIds
            // Sử dụng Collection để có thể dùng put() method
            $existingTags = collect([]);
            if (! empty($tagIds)) {
                $existingTags = Tag::whereIn('id', $tagIds)
                    ->where('entity_type', Post::class)
                    ->select('id', 'name', 'slug', 'description', 'is_active')
                    ->get()
                    ->unique('name')
                    ->keyBy('id');

                Log::debug('PostService::syncTags - Found existing tags', [
                    'found_count' => $existingTags->count(),
                    'tag_ids_searched' => $tagIds,
                    'found_tag_ids' => $existingTags->pluck('id')->toArray(),
                    'missing_tag_ids' => array_diff($tagIds, $existingTags->pluck('id')->toArray()),
                ]);
            }

            // Xử lý tag names từ input (tags mới)
            $allTagNames = [];
            if (! empty($tagNames) && is_string($tagNames) && trim($tagNames) !== '') {
                $newTagNames = $this->parseTagNames($tagNames);
                $allTagNames = array_merge($allTagNames, $newTagNames);
                Log::debug('PostService::syncTags - Parsed tag names', [
                    'parsed_tag_names' => $newTagNames,
                ]);
            }

            // Nếu không có tagIds và không có tagNames, xóa hết tags
            if (empty($tagIds) && empty($allTagNames)) {
                $post->tag_ids = null;
                $post->saveQuietly();
                Log::debug('PostService::syncTags - No tags, cleared all');

                return;
            }

            // Lấy thêm tag names từ existing tags
            foreach ($existingTags as $tag) {
                $allTagNames[] = $tag->name;
            }

            // Loại bỏ duplicate và tìm hoặc tạo tags
            $allTagNames = array_unique(array_map('trim', $allTagNames));
            $finalTagIds = [];

            // QUAN TRỌNG: Thêm tag_ids từ existing tags vào finalTagIds trước
            // để đảm bảo tags cũ được giữ lại
            foreach ($existingTags as $tag) {
                $finalTagIds[] = (int) $tag->id;
            }

            // Tối ưu: Cache tag names đã tìm thấy để tránh query lại
            $tagNameCache = [];
            foreach ($existingTags as $tag) {
                $tagNameCache[strtolower(trim($tag->name))] = (int) $tag->id;
            }

            Log::debug('PostService::syncTags - Processing tag names', [
                'all_tag_names' => $allTagNames,
                'existing_tag_ids_added' => $finalTagIds,
            ]);

            // Tối ưu: Tìm tất cả tags cần thiết trong 1 query thay vì query từng tag
            $tagNamesToFind = [];
            foreach ($allTagNames as $tagName) {
                if (empty($tagName)) {
                    continue;
                }
                $tagNameLower = strtolower(trim($tagName));
                // Chỉ tìm nếu chưa có trong cache
                if (! isset($tagNameCache[$tagNameLower])) {
                    $tagNamesToFind[] = $tagNameLower;
                }
            }

            // Query tất cả tags cần tìm trong 1 lần (tối ưu performance)
            if (! empty($tagNamesToFind)) {
                $foundTagsByName = Tag::where('entity_type', Post::class)
                    ->where(function ($query) use ($tagNamesToFind) {
                        foreach ($tagNamesToFind as $tagNameLower) {
                            $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$tagNameLower]);
                        }
                    })
                    ->orderBy('id', 'asc')
                    ->get()
                    ->unique(function ($tag) {
                        return strtolower(trim($tag->name));
                    })
                    ->keyBy(function ($tag) {
                        return strtolower(trim($tag->name));
                    });

                // Thêm vào cache và existingTags
                foreach ($foundTagsByName as $tagNameLower => $tag) {
                    $tagNameCache[$tagNameLower] = (int) $tag->id;
                    $existingTags->put((int) $tag->id, $tag);
                }
            }

            foreach ($allTagNames as $tagName) {
                if (empty($tagName)) {
                    continue;
                }

                $tagNameLower = strtolower(trim($tagName));
                $tagFound = false;
                $tagId = null;

                // 1. Kiểm tra trong cache (từ existing tags hoặc đã query)
                if (isset($tagNameCache[$tagNameLower])) {
                    $tagFound = true;
                    $tagId = $tagNameCache[$tagNameLower];
                }

                // 3. Nếu vẫn không tìm thấy, tạo tag mới
                if (! $tagFound) {
                    $baseSlug = Str::slug($tagName);
                    $uniqueSlug = $baseSlug;
                    $counter = 1;
                    while (Tag::where('slug', $uniqueSlug)->exists()) {
                        $uniqueSlug = $baseSlug.'-'.$counter;
                        $counter++;
                    }

                    $newTag = Tag::create([
                        'name' => $tagName,
                        'slug' => $uniqueSlug,
                        'description' => null,
                        'is_active' => true,
                        'usage_count' => 0,
                        'entity_id' => $post->id, // Phải set entity_id vì database không cho phép null
                        'entity_type' => Post::class,
                    ]);
                    $tagId = (int) $newTag->id;
                    $existingTags->put($tagId, $newTag);
                    Log::debug('PostService::syncTags - Created new tag', [
                        'tag_id' => $tagId,
                        'tag_name' => $tagName,
                    ]);
                }

                // 4. Thêm vào finalTagIds nếu chưa có
                if ($tagId && ! in_array($tagId, $finalTagIds)) {
                    $finalTagIds[] = $tagId;
                }
            }

            // Cập nhật tag_ids trong post (loại bỏ duplicate và đảm bảo là integer)
            $finalTagIds = array_values(array_unique(array_map('intval', $finalTagIds)));
            $post->tag_ids = ! empty($finalTagIds) ? $finalTagIds : null;
            $post->saveQuietly();

            Log::debug('PostService::syncTags - Completed', [
                'final_tag_ids' => $post->tag_ids,
                'final_tag_ids_count' => is_array($post->tag_ids) ? count($post->tag_ids) : 0,
            ]);
        } catch (\Exception $e) {
            Log::error('PostService::syncTags - Error syncing tags', [
                'post_id' => $post->id,
                'tag_ids' => $tagIds,
                'tag_names' => $tagNames,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse tag names từ string (phân cách bằng dấu phẩy)
     */
    protected function parseTagNames(string $tagNames): array
    {
        return array_filter(
            array_map('trim', explode(',', $tagNames)),
            fn ($name) => ! empty($name)
        );
    }

    /**
     * Sync images với bảng images
     */
    protected function syncImages(Post $post, array $imageIds): void
    {
        $keepIds = [];

        foreach ($imageIds as $order => $imageData) {
            // Nếu là Image ID (số nguyên)
            if (is_numeric($imageData)) {
                $imageId = (int) $imageData;
                $image = Image::find($imageId);
                if ($image) {
                    $keepIds[] = $image->id;
                }

                continue;
            }

            // Nếu là tên file (string)
            if (is_string($imageData) && ! empty(trim($imageData))) {
                $filename = trim($imageData);

                // Loại bỏ path nếu có, chỉ lấy tên file
                $filename = basename($filename);

                // Tìm Image record có url = filename hoặc url chứa filename trong path posts
                $image = Image::where('url', $filename)
                    ->orWhere('url', 'like', '%/posts/'.$filename)
                    ->orWhere('url', 'like', 'posts/'.$filename)
                    ->first();

                if ($image) {
                    // Cập nhật url nếu cần để đảm bảo đúng format
                    if (! Str::contains($image->url, 'posts/')) {
                        $image->url = $filename;
                        $image->saveQuietly();
                    }
                    $keepIds[] = $image->id;
                } else {
                    // Tạo Image record mới với chỉ tên file (không có path)
                    $newImage = Image::create([
                        'url' => $filename,
                        'title' => null,
                        'alt' => null,
                        'is_primary' => $order === 0,
                        'order' => $order,
                    ]);
                    $keepIds[] = $newImage->id;
                }
            }
        }

        // Cập nhật image_ids trong post
        $post->image_ids = ! empty($keepIds) ? $keepIds : null;
        $post->saveQuietly();
    }
}
