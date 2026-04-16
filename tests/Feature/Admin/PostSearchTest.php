<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_exact_prefix_and_contains_matches_for_full_phrase(): void
    {
        $admin = $this->createAdmin();

        $exact = $this->createPost('Cảm biến áp suất', [
            'published_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);
        $prefix = $this->createPost('Cảm biến áp suất lốp', [
            'published_at' => Carbon::parse('2026-01-02 10:00:00'),
        ]);
        $contains = $this->createPost('Review cảm biến áp suất', [
            'published_at' => Carbon::parse('2026-01-03 10:00:00'),
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => 'Cảm biến áp suất',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            $exact->title,
            $prefix->title,
            $contains->title,
        ]);
    }

    public function test_it_falls_back_to_the_longest_matching_sub_phrase_only_when_full_phrase_has_no_result(): void
    {
        $admin = $this->createAdmin();

        $longestMatch = $this->createPost('Cảm biến áp suất lốp');
        $looserMatch = $this->createPost('Áp suất lốp điện tử');

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => 'Cảm biến áp suất lốp xe',
        ]));

        $response->assertOk();
        $response->assertSeeText($longestMatch->title);
        $response->assertDontSeeText($looserMatch->title);
    }

    public function test_it_does_not_fallback_to_slug_when_title_has_matches(): void
    {
        $admin = $this->createAdmin();

        $titleMatch = $this->createPost('Bài viết abc xyz', [
            'slug' => 'bai-viet-khac',
        ]);
        $slugOnlyMatch = $this->createPost('Nội dung khác hoàn toàn', [
            'slug' => 'abc-xyz',
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => 'abc xyz',
        ]));

        $response->assertOk();
        $response->assertSeeText($titleMatch->title);
        $response->assertDontSeeText($slugOnlyMatch->title);
    }

    public function test_it_falls_back_to_slug_when_title_has_no_matches(): void
    {
        $admin = $this->createAdmin();

        $slugMatch = $this->createPost('Tên không liên quan', [
            'slug' => 'cam-bien-ap-suat-lop',
        ]);
        $unrelated = $this->createPost('Bài viết khác');

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => 'cảm biến áp suất lốp',
        ]));

        $response->assertOk();
        $response->assertSeeText($slugMatch->title);
        $response->assertDontSeeText($unrelated->title);
    }

    public function test_it_uses_publish_date_as_tie_breaker_within_the_same_match_stage(): void
    {
        $admin = $this->createAdmin();

        $newer = $this->createPost('Cảm biến áp suất loại mới', [
            'published_at' => Carbon::parse('2026-01-05 10:00:00'),
        ]);
        $older = $this->createPost('Cảm biến áp suất loại cũ', [
            'published_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => 'Cảm biến áp suất loại',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([
            $newer->title,
            $older->title,
        ]);
    }

    public function test_search_still_combines_with_existing_filters(): void
    {
        $admin = $this->createAdmin();
        $allowedAuthor = $this->createAdmin([
            'email' => 'writer-filter@example.com',
            'role' => Account::ROLE_WRITER,
        ]);
        $otherAuthor = $this->createAdmin([
            'email' => 'writer-other@example.com',
            'role' => Account::ROLE_WRITER,
        ]);
        $allowedCategory = $this->createCategory('Tin tức cảm biến');
        $otherCategory = $this->createCategory('Tin tức khác');

        $kept = $this->createPost('Cảm biến áp suất chính xác', [
            'status' => 'published',
            'category_id' => $allowedCategory->id,
            'account_id' => $allowedAuthor->id,
            'created_by' => $allowedAuthor->id,
        ]);
        $wrongStatus = $this->createPost('Cảm biến áp suất đang nháp', [
            'status' => 'draft',
            'category_id' => $allowedCategory->id,
            'account_id' => $allowedAuthor->id,
            'created_by' => $allowedAuthor->id,
        ]);
        $wrongCategory = $this->createPost('Cảm biến áp suất sai danh mục', [
            'status' => 'published',
            'category_id' => $otherCategory->id,
            'account_id' => $allowedAuthor->id,
            'created_by' => $allowedAuthor->id,
        ]);
        $wrongAuthor = $this->createPost('Cảm biến áp suất sai tác giả', [
            'status' => 'published',
            'category_id' => $allowedCategory->id,
            'account_id' => $otherAuthor->id,
            'created_by' => $otherAuthor->id,
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => 'Cảm biến áp suất',
            'status' => 'published',
            'category_id' => $allowedCategory->id,
            'author_id' => $allowedAuthor->id,
        ]));

        $response->assertOk();
        $response->assertSeeText($kept->title);
        $response->assertDontSeeText($wrongStatus->title);
        $response->assertDontSeeText($wrongCategory->title);
        $response->assertDontSeeText($wrongAuthor->title);
    }

    public function test_pagination_links_keep_the_search_query_string(): void
    {
        $admin = $this->createAdmin();
        $keyword = 'Tìm kiếm phân trang';

        for ($i = 1; $i <= 21; $i++) {
            $this->createPost("{$keyword} {$i}");
        }

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => $keyword,
            'per_page' => 20,
        ]));

        $response->assertOk();
        $response->assertSee('page=2', false);
        $response->assertSee(http_build_query(['search' => $keyword]), false);
    }

    public function test_search_normalizes_extra_whitespace(): void
    {
        $admin = $this->createAdmin();

        $matched = $this->createPost('Cảm biến áp suất');
        $unrelated = $this->createPost('Van điện từ');

        $response = $this->actingAs($admin, 'web')->get(route('admin.posts.index', [
            'search' => '   Cảm    biến   áp   suất   ',
        ]));

        $response->assertOk();
        $response->assertSeeText($matched->title);
        $response->assertDontSeeText($unrelated->title);
    }

    private function createAdmin(array $overrides = []): Account
    {
        static $sequence = 1;

        $account = Account::create(array_merge([
            'name' => 'Admin '.$sequence,
            'email' => 'admin'.$sequence.'@example.com',
            'password' => 'password',
            'role' => Account::ROLE_ADMIN,
            'status' => Account::STATUS_ACTIVE,
        ], $overrides));

        $sequence++;

        return $account;
    }

    private function createCategory(string $name): Category
    {
        static $sequence = 1;

        $category = Category::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.$sequence,
            'is_active' => true,
        ]);

        $sequence++;

        return $category;
    }

    private function createPost(string $title, array $overrides = []): Post
    {
        static $sequence = 1;

        $author = $overrides['author'] ?? $this->createAdmin([
            'email' => 'post-author'.$sequence.'@example.com',
            'role' => Account::ROLE_WRITER,
        ]);
        $categoryId = $overrides['category_id'] ?? $this->createCategory('Danh mục '.$sequence)->id;
        $publishedAt = $overrides['published_at'] ?? Carbon::parse('2026-01-01 08:00:00')->addDays($sequence);

        $data = array_merge([
            'title' => $title,
            'slug' => Str::slug($title).'-'.$sequence,
            'status' => 'published',
            'account_id' => $author->id,
            'category_id' => $categoryId,
            'created_by' => $author->id,
            'published_at' => $publishedAt,
            'created_at' => $publishedAt,
            'updated_at' => $publishedAt,
        ], $overrides);

        unset($data['author']);

        $post = Post::create($data);
        $sequence++;

        return $post;
    }
}
