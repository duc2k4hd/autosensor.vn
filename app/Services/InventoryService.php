<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InventoryMovement;
use App\Models\Product;

class InventoryService
{
    /**
     * Điều chỉnh tồn kho và ghi lại log.
     */
    public function adjustStock(Product $product, int $quantityChange, string $type, ?Account $account = null, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null): InventoryMovement
    {
        $before = (int) ($product->stock_quantity ?? 0);
        $after = $before + $quantityChange;

        if ($after < 0) {
            throw new \RuntimeException('Tồn kho không đủ để trừ bớt.');
        }

        $product->stock_quantity = $after;
        $product->save();

        $movement = InventoryMovement::create([
            'product_id' => $product->id,
            'quantity_change' => $quantityChange,
            'stock_before' => $before,
            'stock_after' => $after,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'account_id' => $account?->id,
            'note' => $note,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Gửi thông báo cho admin nếu tồn kho xuống thấp / hết hàng
        $lowThreshold = 5;
        if ($before > 0 && $after <= 0) {
            app(\App\Services\NotificationService::class)->notifyStockAlert(
                $product->id,
                (string) $product->sku,
                (string) $product->name,
                0,
                true
            );
        } elseif ($before > $lowThreshold && $after <= $lowThreshold && $after > 0) {
            app(\App\Services\NotificationService::class)->notifyStockAlert(
                $product->id,
                (string) $product->sku,
                (string) $product->name,
                $after,
                false
            );
        }

        return $movement;
    }

    /**
     * Điều chỉnh tồn kho cho variant và ghi lại log.
     */
    public function adjustVariantStock(\App\Models\ProductVariant $variant, int $quantityChange, string $type, ?Account $account = null, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null): void
    {
        $before = (int) ($variant->stock_quantity ?? 0);
        $after = $before + $quantityChange;

        if ($after < 0) {
            throw new \RuntimeException('Tồn kho variant không đủ để trừ bớt.');
        }

        $variant->stock_quantity = $after;
        $variant->save();

        // Gửi thông báo cho admin nếu tồn kho xuống thấp / hết hàng
        $lowThreshold = 5;
        $product = $variant->product;

        if ($before > 0 && $after <= 0) {
            app(\App\Services\NotificationService::class)->notifyVariantStockAlert(
                $variant->id,
                $product->id,
                (string) ($variant->sku ?? $product->sku),
                (string) $product->name,
                (string) $variant->name,
                0,
                true
            );
        } elseif ($before > $lowThreshold && $after <= $lowThreshold && $after > 0) {
            app(\App\Services\NotificationService::class)->notifyVariantStockAlert(
                $variant->id,
                $product->id,
                (string) ($variant->sku ?? $product->sku),
                (string) $product->name,
                (string) $variant->name,
                $after,
                false
            );
        }
    }

    /**
     * Kiểm tra đủ tồn kho cho danh sách item, nếu thiếu ném exception.
     *
     * @param  array<int, array{product:Product, quantity:int}>  $items
     */
    public function assertSufficientStock(array $items): void
    {
        foreach ($items as $row) {
            /** @var Product $product */
            $product = $row['product'];
            $qty = (int) $row['quantity'];

            if (is_null($product->stock_quantity)) {
                continue;
            }

            if ($qty <= 0) {
                continue;
            }

            if ($product->stock_quantity < $qty) {
                throw new \RuntimeException(
                    'Sản phẩm "'.$product->name.'" không đủ tồn kho (còn '.$product->stock_quantity.', yêu cầu '.$qty.').'
                );
            }
        }
    }
}
