<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetUnpaidOrders extends Command
{
    protected $signature = 'orders:reset-unpaid {--days=7 : Số ngày để reset đơn hàng}';

    protected $description = 'Reset đơn hàng chưa thanh toán hoặc chưa xác nhận sau N ngày và trả về số lượng sản phẩm';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("🔄 Đang tìm đơn hàng chưa thanh toán/chưa xác nhận trước ngày {$cutoffDate->format('d/m/Y H:i')}...");

        // Tìm các đơn hàng cần reset
        // Điều kiện:
        // - payment_status != 'paid' HOẶC delivery_status != 'confirmed'
        // - created_at < cutoffDate
        // - status != 'cancelled' và status != 'completed'
        $orders = Order::where('created_at', '<', $cutoffDate)
            ->where(function ($query) {
                $query->where('payment_status', '!=', 'paid')
                    ->orWhere('delivery_status', '!=', 'confirmed');
            })
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['items.product', 'items.variant'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('✅ Không có đơn hàng nào cần reset.');

            return Command::SUCCESS;
        }

        $this->info("📦 Tìm thấy {$orders->count()} đơn hàng cần reset.");

        $totalQuantityRestored = 0;
        $restoredProducts = [];
        $restoredVariants = [];

        DB::beginTransaction();

        try {
            foreach ($orders as $order) {
                $this->line("  - Đơn hàng #{$order->code} (ID: {$order->id})");

                // Đếm số lượng sản phẩm cần trả về (từ variant hoặc product)
                foreach ($order->items as $item) {
                    $quantity = $item->quantity;

                    // Nếu có variant, hoàn kho từ variant
                    if ($item->product_variant_id && $item->variant) {
                        $variantId = $item->product_variant_id;
                        if (! isset($restoredVariants[$variantId])) {
                            $restoredVariants[$variantId] = 0;
                        }
                        $restoredVariants[$variantId] += $quantity;
                    } else {
                        // Nếu không có variant, hoàn kho từ product
                        $productId = $item->product_id;
                        if (! isset($restoredProducts[$productId])) {
                            $restoredProducts[$productId] = 0;
                        }
                        $restoredProducts[$productId] += $quantity;
                    }

                    $totalQuantityRestored += $quantity;
                }

                // Cập nhật trạng thái đơn hàng
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                    'delivery_status' => 'cancelled',
                    'admin_note' => ($order->admin_note ?? '')."\n[Auto-cancelled] Đơn hàng tự động hủy sau {$days} ngày chưa thanh toán/xác nhận - ".now()->format('d/m/Y H:i'),
                ]);

                $this->line('    ✓ Đã hủy đơn hàng');
            }

            // Trả về số lượng sản phẩm vào kho từ variant
            foreach ($restoredVariants as $variantId => $quantity) {
                $variant = \App\Models\ProductVariant::find($variantId);

                if ($variant) {
                    $oldStock = $variant->stock_quantity ?? 0;
                    $newStock = $oldStock + $quantity;

                    $variant->update([
                        'stock_quantity' => $newStock,
                    ]);

                    $this->line("    ✓ Biến thể #{$variantId} ({$variant->name}): +{$quantity} (Từ {$oldStock} → {$newStock})");
                } else {
                    $this->warn("    ⚠ Biến thể #{$variantId} không tồn tại, bỏ qua.");
                }
            }

            // Trả về số lượng sản phẩm vào kho từ product
            foreach ($restoredProducts as $productId => $quantity) {
                $product = Product::find($productId);

                if ($product) {
                    $oldStock = $product->stock_quantity ?? 0;
                    $newStock = $oldStock + $quantity;

                    $product->update([
                        'stock_quantity' => $newStock,
                    ]);

                    $this->line("    ✓ Sản phẩm #{$productId} ({$product->name}): +{$quantity} (Từ {$oldStock} → {$newStock})");
                } else {
                    $this->warn("    ⚠ Sản phẩm #{$productId} không tồn tại, bỏ qua.");
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ Hoàn thành!');
            $this->info("   - Số đơn hàng đã reset: {$orders->count()}");
            $this->info("   - Tổng số lượng sản phẩm đã trả về: {$totalQuantityRestored}");
            $this->info('   - Số sản phẩm khác nhau: '.count($restoredProducts));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("❌ Lỗi khi reset đơn hàng: {$e->getMessage()}");
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
