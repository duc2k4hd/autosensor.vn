<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HideProductsBySku extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:hide-from-file {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ẩn sản phẩm dựa trên danh sách SKU từ file text (khớp chính xác hoa thường)';

    /**
     * Hàm chuẩn hóa SKU: loại bỏ mọi ký tự không phải chữ và số, sau đó viết thường.
     * Ví dụ: "E5CC-RX2ASM-004" -> "e5ccrx2asm004"
     */
    private function normalizeSku($sku)
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $sku));
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("Không tìm thấy file: $filePath");

            return 1;
        }

        $this->info("Đang đọc file: $filePath");

        // 1. Đọc và chuẩn hóa SKU từ file
        $fileSkus = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $normalizedFileSkus = [];
        foreach ($fileSkus as $line) {
            $normalized = $this->normalizeSku($line);
            if (! empty($normalized)) {
                $normalizedFileSkus[$normalized] = true; // Sử dụng key để tối ưu tìm kiếm
            }
        }

        if (empty($normalizedFileSkus)) {
            $this->warn('Không tìm thấy SKU nào hiệu quả trong file sau khi chuẩn hóa.');

            return 0;
        }

        $this->info('Phát hiện '.count($normalizedFileSkus).' mẫu SKU chuẩn hóa từ file.');

        // 2. Lấy toàn bộ sản phẩm từ DB để chuẩn hóa và so khớp
        // Vì số lượng sản phẩm không quá lớn (~chưa đến 1000), xử lý trong PHP sẽ linh hoạt và chính xác nhất
        $this->info('Đang tải dữ liệu từ cơ sở dữ liệu...');
        $products = Product::select('id', 'sku')->get();

        $idsToHide = [];
        foreach ($products as $product) {
            $normalizedDbSku = $this->normalizeSku($product->sku);
            if (isset($normalizedFileSkus[$normalizedDbSku])) {
                $idsToHide[] = $product->id;
            }
        }

        $totalFound = count($idsToHide);
        if ($totalFound === 0) {
            $this->warn('Không tìm thấy sản phẩm nào khớp với danh sách SKU (sau khi đã chuẩn hóa).');

            return 0;
        }

        $this->info("Tìm thấy $totalFound sản phẩm khớp. Bắt đầu ẩn...");

        // 3. Cập nhật trạng thái tạm ẩn theo ID (Sử dụng chunk để an toàn)
        $updatedCount = 0;
        foreach (array_chunk($idsToHide, 500) as $chunk) {
            $affected = Product::whereIn('id', $chunk)->update(['is_active' => false]);
            $updatedCount += $affected;
            $this->line("Đang ẩn... $updatedCount/$totalFound");
        }

        $this->info('========================================');
        $this->info('Hoàn tất!');
        $this->info("Tổng số sản phẩm đã được ẩn (linh hoạt): $updatedCount");
        $this->info('========================================');

        return 0;
    }
}
