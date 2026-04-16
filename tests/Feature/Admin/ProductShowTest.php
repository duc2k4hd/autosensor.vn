<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    protected array $createdImageFiles = [];

    public function test_show_page_renders_read_only_view_without_creating_lock(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $brand = $this->createBrand();
        $product = $this->createProduct($admin, [
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show', $product));

        $response->assertOk();
        $response->assertViewIs('admins.products.show');
        $response->assertSeeText($product->name);
        $response->assertSeeText($product->sku);
        $response->assertSeeText($category->name);
        $response->assertSeeText($brand->name);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'locked_by' => null,
        ]);
    }

    public function test_show_page_uses_placeholder_when_product_has_no_image(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct($admin, [
            'image_ids' => [],
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show', $product));

        $response->assertOk();
        $response->assertSee('no-image.webp', false);
    }

    public function test_show_page_shows_primary_image_when_product_has_image(): void
    {
        $admin = $this->createAdmin();
        $image = $this->createImage('product-show-main.jpg');
        $product = $this->createProduct($admin, [
            'image_ids' => [$image->id],
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show', $product));

        $response->assertOk();
        $response->assertSee('product-show-main.jpg', false);
    }

    public function test_show_fragment_images_returns_gallery_items(): void
    {
        $admin = $this->createAdmin();
        $image = $this->createImage('fragment-gallery.jpg', [
            'title' => 'Ảnh gallery',
            'alt' => 'Alt gallery',
        ]);
        $product = $this->createProduct($admin, [
            'image_ids' => [$image->id],
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show-fragment', [
            'product' => $product->id,
            'section' => 'images',
        ]));

        $response->assertOk();
        $response->assertSeeText('Ảnh gallery');
        $response->assertSeeText('Alt gallery');
    }

    public function test_show_fragment_faqs_returns_product_faqs(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct($admin);

        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'FAQ test?',
            'answer' => 'Câu trả lời test',
            'order' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show-fragment', [
            'product' => $product->id,
            'section' => 'faqs',
        ]));

        $response->assertOk();
        $response->assertSeeText('FAQ test?');
        $response->assertSeeText('Câu trả lời test');
    }

    public function test_show_fragment_how_tos_returns_product_how_tos(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct($admin);

        ProductHowTo::create([
            'product_id' => $product->id,
            'title' => 'Hướng dẫn lắp đặt',
            'description' => 'Mô tả how-to',
            'steps' => ['Bước 1', 'Bước 2'],
            'supplies' => ['Dụng cụ 1'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show-fragment', [
            'product' => $product->id,
            'section' => 'how-tos',
        ]));

        $response->assertOk();
        $response->assertSeeText('Hướng dẫn lắp đặt');
        $response->assertSeeText('Bước 1');
        $response->assertSeeText('Dụng cụ 1');
    }

    public function test_show_fragment_variants_returns_product_variants(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct($admin);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Biến thể 1m',
            'sku' => 'VAR-001',
            'price' => 150000,
            'sale_price' => 120000,
            'cost_price' => 100000,
            'stock_quantity' => 5,
            'is_active' => true,
            'sort_order' => 1,
            'note' => 'Ghi chú biến thể',
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.show-fragment', [
            'product' => $product->id,
            'section' => 'variants',
        ]));

        $response->assertOk();
        $response->assertSeeText('Biến thể 1m');
        $response->assertSeeText('VAR-001');
        $response->assertSeeText('Ghi chú biến thể');
    }

    public function test_products_index_contains_show_and_edit_links(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct($admin);

        $response = $this->actingAs($admin, 'web')->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee(route('admin.products.show', $product), false);
        $response->assertSee(route('admin.products.edit', $product), false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->createdImageFiles as $filePath) {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    private function createAdmin(array $overrides = []): Account
    {
        static $sequence = 1;

        $account = Account::create(array_merge([
            'name' => 'Admin ' . $sequence,
            'email' => 'product-admin-' . $sequence . '@example.com',
            'password' => 'password',
            'role' => Account::ROLE_ADMIN,
            'status' => Account::STATUS_ACTIVE,
        ], $overrides));

        $sequence++;

        return $account;
    }

    private function createCategory(array $overrides = []): Category
    {
        static $sequence = 1;

        $category = Category::create(array_merge([
            'name' => 'Danh mục ' . $sequence,
            'slug' => 'danh-muc-' . $sequence,
            'is_active' => true,
            'order' => $sequence,
        ], $overrides));

        $sequence++;

        return $category;
    }

    private function createBrand(array $overrides = []): Brand
    {
        static $sequence = 1;

        $brand = Brand::create(array_merge([
            'name' => 'Hãng ' . $sequence,
            'slug' => 'hang-' . $sequence,
            'is_active' => true,
            'order' => $sequence,
        ], $overrides));

        $sequence++;

        return $brand;
    }

    private function createProduct(Account $admin, array $overrides = []): Product
    {
        static $sequence = 1;

        $product = Product::create(array_merge([
            'sku' => 'PROD-' . $sequence,
            'name' => 'Sản phẩm test ' . $sequence,
            'slug' => 'san-pham-test-' . $sequence . '-' . Str::lower(Str::random(6)),
            'short_description' => '<p>Mô tả ngắn test</p>',
            'description' => '<p>Mô tả chi tiết test</p>',
            'price' => 100000,
            'sale_price' => 90000,
            'cost_price' => 70000,
            'stock_quantity' => 10,
            'meta_title' => 'Meta title test',
            'meta_description' => 'Meta description test',
            'meta_keywords' => ['cam-bien', 'ap-suat'],
            'meta_canonical' => 'https://example.com/san-pham-test-' . $sequence,
            'tag_ids' => [],
            'image_ids' => [],
            'link_catalog' => ['clients/assets/catalog/test.pdf'],
            'video_url' => 'https://www.youtube.com/embed/test-video',
            'is_featured' => false,
            'is_active' => true,
            'created_by' => $admin->id,
        ], $overrides));

        $sequence++;

        return $product;
    }

    private function createImage(string $filename, array $overrides = []): Image
    {
        $imageDir = public_path('clients/assets/img/clothes');
        File::ensureDirectoryExists($imageDir);

        $filePath = $imageDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, 'test-image');
        $this->createdImageFiles[] = $filePath;

        return Image::create(array_merge([
            'url' => $filename,
            'title' => 'Ảnh test',
            'alt' => 'Alt test',
            'is_primary' => true,
            'order' => 1,
        ], $overrides));
    }
}