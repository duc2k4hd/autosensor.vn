<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Support\Facades\DB;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Common\Entity\Row;

class PostImportExportService
{
    /*
    |--------------------------------------------------------------------------
    | EXPORT TEMPLATE
    |--------------------------------------------------------------------------
    */

    public function exportTemplate(string $path): void
    {
        $writer = new XLSXWriter();
        $writer->openToFile($path);

        $headers = [
            'title','slug','status','category_slug','tags',
            'excerpt','content','image_paths','published_at',
            'created_by','meta_title','meta_description','meta_keywords',
        ];

        $writer->addRow(Row::fromValues($headers));

        $example = [
            'Ví dụ tiêu đề bài viết',
            'vi-du-tieu-de',
            'published',
            'tu-dong-hoa',
            'tag1,tag2',
            'Đoạn mô tả ngắn',
            'Nội dung bài viết đầy đủ...',
            'posts/image1.jpg,posts/image2.jpg',
            now()->toDateTimeString(),
            1,
            'Meta title ví dụ',
            'Meta description ví dụ',
            'keyword1,keyword2',
        ];

        $writer->addRow(Row::fromValues($example));
        $writer->close();
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT ALL POSTS
    |--------------------------------------------------------------------------
    */

    public function exportAll(string $path): void
    {
        $writer = new XLSXWriter();
        $writer->openToFile($path);

        $headers = [
            'id','title','slug','status','category_slug','tags',
            'excerpt','content','image_paths','published_at',
            'created_by','meta_title','meta_description','meta_keywords'
        ];

        $writer->addRow(Row::fromValues($headers));

        Post::orderBy('id')->chunk(500, function ($posts) use ($writer) {
            foreach ($posts as $post) {
                $row = [
                    $post->id,
                    $post->title,
                    $post->slug,
                    $post->status,
                    optional($post->category)->slug ?? '',
                    is_array($post->tag_ids)
                        ? implode(',', $this->resolveTagSlugs($post->tag_ids))
                        : '',
                    $post->excerpt ?? '',
                    $post->content ?? '',
                    $post->images->pluck('url')->implode(','),
                    optional($post->published_at)?->toDateTimeString() ?? '',
                    $post->created_by ?? '',
                    $post->meta_title ?? '',
                    $post->meta_description ?? '',
                    is_array($post->meta_keywords)
                        ? implode(',', $post->meta_keywords)
                        : ($post->meta_keywords ?? ''),
                ];

                $writer->addRow(Row::fromValues($row));
            }
        });

        $writer->close();
    }

    protected function resolveTagSlugs(array $tagIds): array
    {
        return Tag::whereIn('id', $tagIds)->pluck('slug')->all();
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT FILE
    |--------------------------------------------------------------------------
    */

    public function importFromFile(string $path): array
    {
        $reader = new XLSXReader();
        $reader->open($path);

        $report = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $headers = null;
        $rowsBuffer = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {

                $cells = [];
                $rowValues = method_exists($row, 'toArray') ? $row->toArray() : $row;
                foreach ($rowValues as $value) {
                    if ($value instanceof \DateTimeInterface) {
                        $cells[] = $value->format('Y-m-d H:i:s');
                    } else {
                        $cells[] = trim((string)$value);
                    }
                }

                if (! $headers) {
                    $headers = array_map('strtolower', $cells);
                    continue;
                }

                $rowsBuffer[] = array_combine($headers, $cells);

                if (count($rowsBuffer) >= 200) {
                    $this->processRows($rowsBuffer, $report);
                    $rowsBuffer = [];
                }
            }
        }

        if (! empty($rowsBuffer)) {
            $this->processRows($rowsBuffer, $report);
        }

        $reader->close();

        return $report;
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESS ROWS
    |--------------------------------------------------------------------------
    */

    public function processRows(array $rows, array &$report): void
    {
        foreach ($rows as $data) {

            $report['processed']++;

            $title = $data['title'] ?? '';
            $slug  = $data['slug'] ?? '';

            if (empty($title) && empty($slug)) {
                $report['skipped']++;
                $keys = implode(', ', array_keys($data));
                $report['errors'][] = "Row {$report['processed']}: missing title and slug. Keys found: [{$keys}]";
                continue;
            }

            try {

                DB::transaction(function () use ($data, &$report) {

                    // -------------------------------------------------
                    // -------------------------------------------------
                    // 1️⃣ FIND EXISTING
                    // -------------------------------------------------

                    $post = null;

                    // Ưu tiên tìm theo ID nếu có
                    if (!empty($data['id']) && is_numeric($data['id'])) {
                        $post = Post::find((int)$data['id']);
                    }

                    // Nếu không có ID hoặc không tìm thấy theo ID, tìm theo SLUG
                    if (!$post && !empty($data['slug'])) {
                        $post = Post::where('slug', $data['slug'])->first();
                    }

                    $isNew = false;
                    if (!$post) {
                        $post = new Post();
                        $isNew = true;
                    }

                    // -------------------------------------------------
                    // 2️⃣ RESOLVE CATEGORY
                    // -------------------------------------------------

                    $categoryId = null;
                    if (isset($data['category_slug']) && $data['category_slug'] !== '') {
                        $cat = Category::where('slug', $data['category_slug'])->first();
                        $categoryId = $cat?->id;
                    }

                    // -------------------------------------------------
                    // 4️⃣ MAP DATA (CHỈ SET KHI TRONG EXCEL CÓ GIÁ TRỊ GỬI LÊN)
                    // -------------------------------------------------

                    if (isset($data['title'])) $post->title = $data['title'];
                    if (isset($data['slug'])) $post->slug = $data['slug'];
                    if (isset($data['status'])) $post->status = $data['status'];

                    if (isset($data['category_slug'])) {
                        $post->category_id = $categoryId;
                    }

                    if (isset($data['excerpt'])) $post->excerpt = $data['excerpt'];
                    if (isset($data['content'])) $post->content = $data['content'];

                    if (isset($data['image_paths']) && $data['image_paths'] !== '') {
                        $imagePaths = array_filter(array_map('trim', explode(',', $data['image_paths'])));
                        $imageIds = [];
                        foreach ($imagePaths as $index => $path) {
                            $filename = basename($path);
                            // Tìm ảnh theo tên file
                            $image = Image::where('url', $filename)
                                ->orWhere('url', 'like', "%/{$filename}")
                                ->first();

                            if (!$image) {
                                // Tạo mới nếu chưa có, dùng title bài viết làm alt/title
                                $image = Image::create([
                                    'url' => $filename,
                                    'title' => $post->title,
                                    'alt' => $post->title,
                                    'is_primary' => ($index === 0),
                                    'order' => $index,
                                ]);
                            } else {
                                // Cập nhật title và alt nếu đã tồn tại theo yêu cầu người dùng
                                $image->update([
                                    'title' => $post->title,
                                    'alt' => $post->title,
                                ]);
                            }
                            $imageIds[] = $image->id;
                        }
                        $post->image_ids = $imageIds;
                    }

                    if (isset($data['published_at']) && $data['published_at'] !== '') {
                        $post->published_at = $data['published_at'];
                    }

                    if (isset($data['created_by']) && $data['created_by'] !== '') {
                        $post->created_by = $data['created_by'];
                        // Nếu là bài mới hoặc account_id đang trống, đồng bộ luôn Tác giả (account_id)
                        if ($isNew || empty($post->account_id)) {
                            $post->account_id = $data['created_by'];
                        }
                    }

                    if (isset($data['meta_title'])) $post->meta_title = $data['meta_title'];
                    if (isset($data['meta_description'])) $post->meta_description = $data['meta_description'];

                    if (isset($data['meta_keywords'])) {
                        $post->meta_keywords = array_filter(array_map('trim', explode(',', $data['meta_keywords'])));
                    }

                    // Kiểm tra thay đổi cơ bản trước khi lưu lần đầu (để lấy ID nếu là bài mới)
                    $basicFieldsDirty = $post->isDirty();
                    $hasTagsColumn = isset($data['tags']);

                    if ($isNew || $basicFieldsDirty) {
                        $post->save();
                    }

                    // -------------------------------------------------
                    // 5️⃣ RESOLVE TAGS (CẦN ID CỦA POST)
                    // -------------------------------------------------

                    if ($hasTagsColumn) {
                        $newTagIds = [];
                        if (!empty($data['tags'])) {
                            $tagSlugs = array_filter(array_map('trim', explode(',', $data['tags'])));
                            foreach ($tagSlugs as $tagSlug) {
                                $tag = Tag::where('slug', $tagSlug)
                                    ->where('entity_type', Post::class)
                                    ->first();

                                if (!$tag) {
                                    $tag = Tag::create([
                                        'slug' => $tagSlug,
                                        'name' => str_replace('-', ' ', ucfirst($tagSlug)),
                                        'entity_type' => Post::class,
                                        'entity_id' => $post->id,
                                        'is_active' => true,
                                        'usage_count' => 0
                                    ]);
                                }
                                $newTagIds[] = $tag->id;
                            }
                        }

                        // Đồng bộ usage_count qua TagService
                        $oldTagIds = $isNew ? [] : ($post->getOriginal('tag_ids') ?? []);
                        app(TagService::class)->updateUsageCountForTags($oldTagIds, $newTagIds);

                        $post->tag_ids = !empty($newTagIds) ? $newTagIds : null;
                        $post->save(); 
                    }

                    // Báo cáo kết quả
                    if ($isNew) {
                        $report['created']++;
                    } elseif ($basicFieldsDirty || (isset($newTagIds) && $newTagIds !== ($oldTagIds ?? []))) {
                        $report['updated']++;
                    } else {
                        $report['skipped']++;
                    }
                });

            } catch (\Throwable $e) {
                $report['errors'][] =
                    "Row {$report['processed']}: {$e->getMessage()}";
            }
        }
    }
}
