<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuffProductReviews extends Command
{
    protected $signature = 'buff:product-reviews
        {--names-file=storage/app/buff/names.txt : File danh sách tên (mỗi dòng 1 tên)}
        {--comments-file=storage/app/buff/comments.txt : File comment (txt: mỗi dòng 1 comment; json: mảng đối tượng có comment và reply)}
        {--admin-replies-file= : File template trả lời admin (mỗi dòng 1 câu trả lời, có thể dùng :name, :product)}
        {--admin-id= : ID admin dùng để reply (mặc định lấy admin đầu tiên)}
        {--mode=range : range|random - range = số review ngẫu nhiên trong min-max, random = ngẫu nhiên trong random-min/random-max}
        {--min=10 : Số review tối thiểu cho mode=range}
        {--max=100 : Số review tối đa cho mode=range}
        {--random-min=10 : Số review tối thiểu cho mode=random}
        {--random-max=200 : Số review tối đa cho mode=random}
        {--five-star-rate=85 : % xác suất ra 5 sao (còn lại ra 4 sao) để tiệm cận 4.5-5}
        {--only-product-ids=* : Chỉ buff các product id này}
        {--active-only : Chỉ buff sản phẩm đang active}
        {--dry-run : Chỉ hiển thị kế hoạch, không ghi dữ liệu}
        {--force : Bỏ qua confirm}';

    protected $description = 'Buff đánh giá (comment + rating) cho tất cả sản phẩm kèm trả lời admin';

    public function handle(): int
    {
        $mode = $this->option('mode');
        if (! in_array($mode, ['range', 'random'], true)) {
            $this->error('Mode phải là range hoặc random');

            return self::FAILURE;
        }

        $min = (int) $this->option('min');
        $max = (int) $this->option('max');
        $randMin = (int) $this->option('random-min');
        $randMax = (int) $this->option('random-max');
        $fiveStarRate = (int) $this->option('five-star-rate');

        if ($min < 1 || $max < $min) {
            $this->error('Giá trị min/max không hợp lệ');

            return self::FAILURE;
        }

        if ($randMin < 1 || $randMax < $randMin) {
            $this->error('Giá trị random-min/random-max không hợp lệ');

            return self::FAILURE;
        }

        if ($fiveStarRate < 0 || $fiveStarRate > 100) {
            $this->error('five-star-rate phải từ 0-100');

            return self::FAILURE;
        }

        $names = $this->loadList($this->option('names-file'), 'names');
        [$commentItems, $comments] = $this->loadCommentsWithReplies($this->option('comments-file'));
        $adminReplies = $this->loadList(
            $this->option('admin-replies-file'),
            'admin replies',
            $this->defaultAdminReplies()
        );

        if (empty($names) || empty($comments)) {
            $this->error('Danh sách names/comments không được trống');

            return self::FAILURE;
        }

        $admin = $this->resolveAdmin($this->option('admin-id'));
        if (! $admin) {
            $this->error('Không tìm thấy admin. Dùng --admin-id để chỉ định hoặc tạo admin trước.');

            return self::FAILURE;
        }

        $productQuery = Product::query();
        if ($this->option('active-only')) {
            $productQuery->where('is_active', true);
        }

        $onlyIds = $this->option('only-product-ids');
        if (! empty($onlyIds)) {
            $productQuery->whereIn('id', $onlyIds);
        }

        $productCount = $productQuery->count();
        if ($productCount === 0) {
            $this->info('Không có sản phẩm nào để buff.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn("Sẽ buff review cho {$productCount} sản phẩm với mode={$mode}.");
            if (! $this->confirm('Tiếp tục?')) {
                return self::SUCCESS;
            }
        }

        $totalInserted = 0;

        $productQuery->orderBy('id')
            ->chunkById(50, function ($products) use (
                $mode,
                $min,
                $max,
                $randMin,
                $randMax,
                $fiveStarRate,
                $names,
                $commentItems,
                $adminReplies,
                $admin,
                &$totalInserted
            ) {
                foreach ($products as $product) {
                    $reviewCount = $this->determineReviewCount(
                        $mode,
                        $min,
                        $max,
                        $randMin,
                        $randMax
                    );

                    if ($reviewCount === 0) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("DRY-RUN | Product #{$product->id} ({$product->name}): {$reviewCount} reviews");
                        continue;
                    }

                    DB::transaction(function () use (
                        $product,
                        $reviewCount,
                        $names,
                        $commentItems,
                        $adminReplies,
                        $admin,
                        $fiveStarRate,
                        &$totalInserted
                    ) {
                        $nextId = ((int) (DB::table('comments')->lockForUpdate()->max('id') ?? 0)) + 1;
                        $parentRows = [];
                        $replyRows = [];

                        for ($i = 0; $i < $reviewCount; $i++) {
                            $name = Arr::random($names);
                            $commentData = Arr::random($commentItems);
                            $content = $commentData['comment'];
                            $rating = $commentData['rating'] ?? $this->generateRating($fiveStarRate);
                            $createdAt = $this->randomTimestamp();

                            $parentId = $nextId++;
                            $parentRows[] = [
                                'id' => $parentId,
                                'account_id' => null,
                                'session_id' => (string) Str::uuid(),
                                'commentable_id' => $product->id,
                                'commentable_type' => Comment::TYPE_PRODUCT,
                                'parent_id' => null,
                                'content' => $content,
                                'name' => $name,
                                'email' => $this->randomEmail($name),
                                'is_approved' => true,
                                'rating' => $rating,
                                'ip' => $this->randomIp(),
                                'user_agent' => 'Mozilla/5.0 (compatible; BuffBot/1.0)',
                                'is_reported' => false,
                                'reports_count' => 0,
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt,
                            ];

                            $replyContent = $commentData['reply'] ?? $this->buildAdminReply($adminReplies, $name, $product->name);
                            $replyAt = $createdAt->copy()->addMinutes(random_int(5, 240));

                            $replyRows[] = [
                                'id' => $nextId++,
                                'account_id' => $admin->id,
                                'session_id' => null,
                                'commentable_id' => $product->id,
                                'commentable_type' => Comment::TYPE_PRODUCT,
                                'parent_id' => $parentId,
                                'content' => $replyContent,
                                'name' => $admin->name ?? 'Admin',
                                'email' => $admin->email,
                                'is_approved' => true,
                                'rating' => null,
                                'ip' => null,
                                'user_agent' => 'Mozilla/5.0 (compatible; BuffBot/1.0)',
                                'is_reported' => false,
                                'reports_count' => 0,
                                'created_at' => $replyAt,
                                'updated_at' => $replyAt,
                            ];

                            $totalInserted++;
                        }

                        if (! empty($parentRows)) {
                            Comment::query()->insert($parentRows);
                        }

                        if (! empty($replyRows)) {
                            Comment::query()->insert($replyRows);
                        }
                    });

                    $this->info("Product #{$product->id} ({$product->name}): +{$reviewCount} reviews");
                }
            });

        if ($this->option('dry-run')) {
            $this->info('Dry-run hoàn tất, không có dữ liệu nào được ghi.');
        } else {
            $this->info("Đã tạo {$totalInserted} review (kèm trả lời admin).");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function loadList(?string $path, string $label, array $fallback = []): array
    {
        if (! $path) {
            return $fallback;
        }

        if (! file_exists($path)) {
            if (! empty($fallback)) {
                $this->warn("Không tìm thấy {$label} file {$path}, dùng danh sách mặc định.");

                return $fallback;
            }

            $this->error("File {$label} không tồn tại: {$path}");

            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines)));

        if (empty($lines) && empty($fallback)) {
            $this->error("File {$label} rỗng: {$path}");
        }

        return empty($lines) ? $fallback : $lines;
    }

    /**
     * Đọc file comments:
     * - txt: mỗi dòng là comment, reply = null
     * - json: mảng các object {comment|content, reply?, rating?}
     *         hoặc object {comments: [], replies?: []}
     *
     * @return array{0: array<int, array{comment:string, reply?:string, rating?:int}>, 1: array<int, string>}
     */
    protected function loadCommentsWithReplies(?string $path): array
    {
        $fallback = [];
        if (! $path) {
            return [$fallback, $fallback];
        }

        if (! file_exists($path)) {
            $this->error("File comments không tồn tại: {$path}");

            return [$fallback, $fallback];
        }

        if (str_ends_with(strtolower($path), '.json')) {
            $json = file_get_contents($path);
            $data = json_decode($json ?? '', true);

            if (! is_array($data)) {
                $this->error("File comments JSON không hợp lệ: {$path}");

                return [$fallback, $fallback];
            }

            // Trường hợp object có keys comments/replies
            if ($this->hasCommentReplyKeys($data)) {
                $comments = array_values(array_filter(array_map('trim', $data['comments'] ?? [])));
                $replies = array_values(array_filter(array_map('trim', $data['replies'] ?? [])));

                $items = [];
                foreach ($comments as $comment) {
                    $item = ['comment' => $comment];
                    if (! empty($replies)) {
                        $item['reply'] = Arr::random($replies);
                    }
                    $items[] = $item;
                }

                return [$items, $comments];
            }

            // Trường hợp mảng các object
            $items = [];
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $comment = $row['comment'] ?? $row['content'] ?? null;
                if (! $comment) {
                    continue;
                }

                $item = ['comment' => trim($comment)];
                if (! empty($row['reply'])) {
                    $item['reply'] = trim((string) $row['reply']);
                }

                if (isset($row['rating'])) {
                    $rating = (int) $row['rating'];
                    if ($rating >= 1 && $rating <= 5) {
                        $item['rating'] = $rating;
                    }
                }

                $items[] = $item;
            }

            $flat = array_column($items, 'comment');

            return [$items, $flat];
        }

        // Fallback: txt
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines)));

        $items = array_map(fn ($c) => ['comment' => $c], $lines);

        return [$items, $lines];
    }

    protected function hasCommentReplyKeys(array $data): bool
    {
        return array_key_exists('comments', $data);
    }

    protected function determineReviewCount(
        string $mode,
        int $min,
        int $max,
        int $randMin,
        int $randMax
    ): int {
        if ($mode === 'range') {
            return random_int($min, $max);
        }

        return random_int($randMin, $randMax);
    }

    protected function generateRating(int $fiveStarRate): int
    {
        $roll = random_int(1, 100);

        return $roll <= $fiveStarRate ? 5 : 4;
    }

    protected function randomTimestamp(): Carbon
    {
        return Carbon::now()
            ->subDays(random_int(0, 45))
            ->subMinutes(random_int(0, 24 * 60));
    }

    protected function randomEmail(string $name): string
    {
        $slug = Str::slug($name, '.');

        return $slug.random_int(1000, 9999).'@example.com';
    }

    protected function randomIp(): string
    {
        return sprintf('%d.%d.%d.%d', random_int(1, 223), random_int(0, 255), random_int(0, 255), random_int(1, 254));
    }

    protected function buildAdminReply(array $adminReplies, string $customerName, string $productName): string
    {
        $template = Arr::random($adminReplies);

        return str_replace(
            [':name', ':product'],
            [$customerName, $productName],
            $template
        );
    }

    protected function resolveAdmin(?string $adminId): ?Account
    {
        $query = Account::query()->where([
            ['role', '=', Account::ROLE_ADMIN],
        ]);

        if ($adminId) {
            $query->where([
                ['id', '=', $adminId],
            ]);
        }

        return $query->orderBy('id')->first();
    }

    /**
     * Mẫu trả lời admin mặc định.
     *
     * @return array<int, string>
     */
    protected function defaultAdminReplies(): array
    {
        return [
            'Cảm ơn :name đã tin tưởng và chọn mua :product! Nếu cần hỗ trợ thêm bạn nhắn cho shop nhé.',
            'Rất vui vì :name hài lòng với :product. Shop luôn sẵn sàng hỗ trợ bạn!',
            'Cảm ơn phản hồi của bạn. :product luôn được bảo hành chính hãng, cần gì bạn cứ chat shop.',
            'Shop ghi nhận góp ý của :name. Chúc bạn trải nghiệm tốt với :product!',
            'Cảm ơn bạn đã đánh giá ủng hộ. Nếu cần hướng dẫn sử dụng :product hãy nhắn shop nhé.',
            'Đội ngũ kỹ thuật sẵn sàng hỗ trợ nếu :name cần thêm thông tin về :product.',
            ':product đang được ưu đãi, cảm ơn :name đã lựa chọn. Chúc bạn dùng tốt!',
            'Cảm ơn :name, mọi thắc mắc về :product bạn cứ liên hệ, shop hỗ trợ 24/7.',
            'Rất trân trọng phản hồi của bạn về :product. Chúc bạn một ngày tốt lành!',
            'Cảm ơn :name! Nếu cần bảo hành/bảo trì :product, bạn nhắn shop để được ưu tiên nhé.',
        ];
    }
}
