<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Tính lại đơn giá cho từng item và cập nhật tổng tiền / số lượng (accessor).
     */
    public function recalculateTotals(Cart $cart): Cart
    {
        $cart->loadMissing(['items.product.currentFlashSaleItem.flashSale', 'items.variant']);

        DB::transaction(function () use ($cart) {
            foreach ($cart->items as $item) {
                if (! $item->product) {
                    continue;
                }

                $item->loadMissing(['product.currentFlashSaleItem.flashSale', 'variant']);

                // Lấy giá từ variant hoặc product
                if ($item->variant && $item->variant->is_active) {
                    $resolvedPrice = (float) $item->variant->display_price;
                } else {
                    $item->product->loadMissing('currentFlashSaleItem.flashSale');
                    $resolvedPrice = $item->product->resolveCartPrice();
                }

                $item->update([
                    'price' => $resolvedPrice,
                ]);
            }
        });

        return $cart->fresh(['items.product', 'items.variant']);
    }

    /**
     * Kiểm tra giỏ hàng trước khi tạo đơn.
     *
     * @return array<string> Danh sách lỗi (rỗng nếu hợp lệ)
     */
    public function validateCart(Cart $cart, bool $skipPriceCheck = false): array
    {
        $errors = [];

        $cart->loadMissing(['items.product.currentFlashSaleItem.flashSale', 'items.variant']);

        if ($cart->items->isEmpty()) {
            $errors[] = 'Giỏ hàng không có sản phẩm nào.';

            return $errors;
        }

        foreach ($cart->items as $item) {
            /** @var Product|null $product */
            $product = $item->product;

            if (! $product) {
                $errors[] = 'Một sản phẩm trong giỏ không còn tồn tại.';

                continue;
            }

            if (! $product->is_active) {
                $errors[] = sprintf('Sản phẩm "%s" đã ngừng kinh doanh.', $product->name);

                continue;
            }

            $item->loadMissing('variant');
            $variant = $item->variant;

            // Kiểm tra variant có thuộc về product không
            if ($variant && $variant->product_id !== $product->id) {
                $errors[] = sprintf('Biến thể của sản phẩm "%s" không hợp lệ.', $product->name);

                continue;
            }

            // Kiểm tra variant có active không
            if ($variant && ! $variant->is_active) {
                $errors[] = sprintf('Biến thể của sản phẩm "%s" đã ngừng kinh doanh.', $product->name);

                continue;
            }

            $requestedQty = (int) ($item->quantity ?? 0);

            if ($requestedQty <= 0) {
                $productName = $variant ? $product->name.' - '.$variant->name : $product->name;
                $errors[] = sprintf('Số lượng của sản phẩm "%s" không hợp lệ.', $productName);

                continue;
            }

            // Kiểm tra tồn kho từ variant hoặc product
            $availableStock = $variant && $variant->is_active ? $variant->stock_quantity : $product->stock_quantity;
            if ($availableStock !== null && $requestedQty > $availableStock) {
                $productName = $variant ? $product->name.' - '.$variant->name : $product->name;
                $errors[] = sprintf(
                    'Sản phẩm "%s" chỉ còn %d sản phẩm trong kho.',
                    $productName,
                    (int) $availableStock
                );
            }

            if (! $skipPriceCheck) {
                // So sánh giá từ variant hoặc product
                $resolvedPrice = $variant && $variant->is_active
                    ? (float) $variant->display_price
                    : $product->resolveCartPrice();

                if ((float) $item->price !== (float) $resolvedPrice) {
                    $productName = $variant ? $product->name.' - '.$variant->name : $product->name;
                    $errors[] = sprintf(
                        'Giá của sản phẩm "%s" đã thay đổi, vui lòng tải lại giỏ hàng.',
                        $productName
                    );
                }
            }
        }

        return array_values(array_unique($errors));
    }
}
