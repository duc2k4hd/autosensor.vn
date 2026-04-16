<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
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
                    is_array($post->image_ids)
                        ? implode(',', $post->image_ids)
                        : '',
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

                $cells = array_map(
                    fn($cell) => trim((string) $cell->getValue()),
                    $row->getCells()
                );

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

    protected function processRows(array $rows, array &$report): void
    {
        foreach ($rows as $data) {

            $report['processed']++;

            $title = $data['title'] ?? '';
            $slug  = $data['slug'] ?? '';

            if (empty($title) && empty($slug)) {
                $report['skipped']++;
                $report['errors'][] =
                    "Row {$report['processed']}: missing title and slug";
                continue;
            }

            try {

                DB::transaction(function () use ($data, &$report) {

                    // -------------------------------------------------
                    // 1️⃣ FIND EXISTING
                    // -------------------------------------------------

                    $post = null;

                    if (!empty($data['id']) && is_numeric($data['id'])) {
                        $post = Post::find((int)$data['id']);
                    }

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
                    if (!empty($data['category_slug'])) {
                        $cat = Category::where('slug', $data['category_slug'])->first();
                        $categoryId = $cat?->id;
                    }

                    // -------------------------------------------------
                    // 3️⃣ RESOLVE TAGS
                    // -------------------------------------------------

                    $tagIds = [];

                    if (!empty($data['tags'])) {
                        $tagSlugs = array_filter(array_map('trim', explode(',', $data['tags'])));

                        foreach ($tagSlugs as $slug) {
                            $tag = Tag::firstOrCreate(
                                [
                                    'slug' => $slug,
                                    'entity_type' => Tag::normalizeEntityType('post')
                                ],
                                [
                                    'name' => $slug,
                                    'is_active' => true
                                ]
                            );

                            $tagIds[] = $tag->id;
                        }
                    }

                    // -------------------------------------------------
                    // 4️⃣ MAP DATA (CHỈ SET KHI CÓ GIÁ TRỊ)
                    // -------------------------------------------------

                    $original = $post->replicate(); // snapshot để so sánh

                    if (!empty($data['title'])) $post->title = $data['title'];
                    if (!empty($data['slug'])) $post->slug = $data['slug'];
                    if (isset($data['status']) && $data['status'] !== '')
                        $post->status = $data['status'];

                    if ($categoryId !== null)
                        $post->category_id = $categoryId;

                    if (!empty($tagIds))
                        $post->tag_ids = $tagIds;

                    if (isset($data['excerpt']))
                        $post->excerpt = $data['excerpt'];

                    if (isset($data['content']))
                        $post->content = $data['content'];

                    if (!empty($data['image_paths'])) {
                        $post->image_ids = array_values(
                            array_filter(array_map('trim', explode(',', $data['image_paths'])))
                        );
                    }

                    if (!empty($data['published_at']))
                        $post->published_at = $data['published_at'];

                    if (!empty($data['created_by']))
                        $post->created_by = $data['created_by'];

                    if (isset($data['meta_title']))
                        $post->meta_title = $data['meta_title'];

                    if (isset($data['meta_description']))
                        $post->meta_description = $data['meta_description'];

                    if (!empty($data['meta_keywords'])) {
                        $post->meta_keywords =
                            array_map('trim', explode(',', $data['meta_keywords']));
                    }

                    // -------------------------------------------------
                    // 5️⃣ CHECK CHANGES
                    // -------------------------------------------------

                    if (!$isNew && !$post->isDirty()) {
                        // Không có thay đổi
                        $report['skipped']++;
                        return;
                    }

                    $post->save();

                    if ($isNew) {
                        $report['created']++;
                    } else {
                        $report['updated']++;
                    }
                });

            } catch (\Throwable $e) {
                $report['errors'][] =
                    "Row {$report['processed']}: {$e->getMessage()}";
            }
        }
    }
}
