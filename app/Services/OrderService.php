<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private VoucherService $voucherService,
        private InventoryService $inventoryService,
    ) {}

    /**
     * Tạo đơn hàng từ dữ liệu form admin (không phụ thuộc giỏ hàng).
     */
    public function createOrder(array $data, array $items): Order
    {
        return $this->persistOrder(null, $data, $items);
    }

    /**
     * Cập nhật đơn hàng từ form admin.
     */
    public function updateOrder(Order $order, array $data, ?array $items = null): Order
    {
        return $this->persistOrder($order, $data, $items ?? []);
    }

    /**
     * Tạo đơn hàng từ giỏ hàng phía admin.
     */
    public function createOrderFromCart(Cart $cart, array $data): Order
    {
        $cart->loadMissing('items.product.currentFlashSaleItem.flashSale');

        $items = [];

        foreach ($cart->items as $item) {
            if (! $item->product) {
                continue;
            }

            $items[] = [
                'product_id' => $item->product_id,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ];
        }

        $order = $this->persistOrder(null, $data, $items, $cart);

        $cart->update(['status' => 'ordered']);

        return $order;
    }

    public function updateOrderStatus(Order $order, string $status, ?string $paymentStatus = null, ?string $deliveryStatus = null): Order
    {
        $payload = ['status' => $status];

        if ($paymentStatus !== null) {
            $payload['payment_status'] = $paymentStatus;
        }

        if ($deliveryStatus !== null) {
            $payload['delivery_status'] = $deliveryStatus;
        }

        $order->update($payload);

        return $order->refresh();
    }

    public function cancelOrder(Order $order, ?string $note = null, bool $restoreStock = true): Order
    {
        if ($order->status === 'cancelled') {
            return $order;
        }

        DB::transaction(function () use ($order, $note, $restoreStock) {
            if ($restoreStock) {
                $order->loadMissing('items.product');
                /** @var \App\Models\Account|null $actor */
                $actor = Auth::user();
                foreach ($order->items as $item) {
                    if (! $item->product) {
                        continue;
                    }

                    $item->loadMissing('variant');

                    // Nếu có variant, hoàn kho từ variant
                    if ($item->variant && $item->variant->is_active) {
                        if ($item->variant->stock_quantity !== null) {
                            $deductQty = (int) $item->quantity;
                            $currentStock = (int) $item->variant->stock_quantity;
                            $item->variant->stock_quantity = $currentStock + $deductQty;
                            $item->variant->save();
                        }
                    } elseif (! is_null($item->product->stock_quantity)) {
                        // Nếu không có variant, hoàn kho từ product
                        app(InventoryService::class)->adjustStock(
                            $item->product,
                            (int) $item->quantity,
                            'order_cancel',
                            $actor,
                            Order::class,
                            $order->id,
                            'Hoàn kho do huỷ đơn '.$order->code
                        );
                    }
                }
            }

            $order->update([
                'status' => 'cancelled',
                'delivery_status' => $order->delivery_status === 'delivered'
                    ? 'returned'
                    : ($order->delivery_status ?: 'cancelled'),
                'admin_note' => trim($order->admin_note."\n".($note ?: '')),
            ]);
        });

        return $order->refresh();
    }

    public function completeOrder(Order $order): Order
    {
        $order->update([
            'status' => 'completed',
        ]);

        return $order->refresh();
    }

    public function recalculateOrderTotals(Order $order): Order
    {
        $order->loadMissing('items');

        $subtotal = $order->items->sum(fn (OrderItem $item) => (float) $item->total_price);

        $shippingFee = (float) ($order->shipping_fee ?? 0);
        $tax = (float) ($order->tax ?? 0);
        $discount = (float) ($order->discount ?? 0);
        $voucherDiscount = (float) ($order->voucher_discount ?? 0);

        $final = max($subtotal + $shippingFee + $tax - $discount - $voucherDiscount, 0);

        $order->update([
            'total_price' => $subtotal,
            'final_price' => $final,
        ]);

        return $order->refresh();
    }

    /**
     * Tạo / cập nhật đơn hàng + items.
     *
     * @param  Cart|null  $sourceCart  Giỏ hàng gốc (nếu tạo từ giỏ)
     */
    protected function persistOrder(?Order $order, array $data, array $items, ?Cart $sourceCart = null): Order
    {
        return DB::transaction(function () use ($order, $data, $items, $sourceCart) {
            $items = array_values(array_filter($items, function ($item) {
                return ! empty($item['product_id']) && (int) ($item['quantity'] ?? 0) > 0;
            }));

            if (empty($items)) {
                throw new \RuntimeException('Đơn hàng phải có ít nhất một sản phẩm hợp lệ.');
            }

            $productIds = array_unique(array_column($items, 'product_id'));
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $variantIds = array_filter(array_column($items, 'product_variant_id'));
            $variants = ! empty($variantIds) ? \App\Models\ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();

            $subtotal = 0;
            $hasFlashSale = false;

            foreach ($items as &$row) {
                $product = $products[$row['product_id']] ?? null;
                if (! $product) {
                    throw new \RuntimeException('Không tìm thấy sản phẩm ID: '.$row['product_id']);
                }

                $variantId = $row['product_variant_id'] ?? null;
                $variant = $variantId && $variants->has($variantId) ? $variants[$variantId] : null;

                // Validate variant thuộc về product
                if ($variant && $variant->product_id !== $product->id) {
                    throw new \RuntimeException('Biến thể không thuộc về sản phẩm này.');
                }

                $qty = (int) ($row['quantity'] ?? 0);

                // Lấy giá từ variant hoặc product
                if ($variant && $variant->is_active) {
                    $price = (float) ($row['price'] ?? $variant->display_price);
                    $availableStock = $variant->stock_quantity;
                } else {
                    $price = (float) ($row['price'] ?? $product->resolveCartPrice());
                    $availableStock = $product->stock_quantity;
                }

                if ($qty <= 0) {
                    throw new \RuntimeException('Số lượng sản phẩm không hợp lệ.');
                }

                // Kiểm tra tồn kho từ variant hoặc product
                if ($availableStock !== null && $availableStock < $qty) {
                    $productName = $variant ? $product->name.' - '.$variant->name : $product->name;
                    throw new \RuntimeException(
                        'Sản phẩm "'.$productName.'" không đủ tồn kho (còn '.$availableStock.', yêu cầu '.$qty.').'
                    );
                }

                $row['quantity'] = $qty;
                $row['price'] = $price;
                $row['total_price'] = $qty * $price;
                $row['product_variant_id'] = $variantId;

                $subtotal += $row['total_price'];

                if ($product->isInFlashSale()) {
                    $hasFlashSale = true;
                }
            }
            unset($row);

            $shippingFee = (float) ($data['shipping_fee'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $voucherDiscount = (float) ($data['voucher_discount'] ?? 0);

            $finalPrice = max($subtotal + $shippingFee + $tax - $discount - $voucherDiscount, 0);

            $payload = [
                'code' => $order?->code ?? $this->generateOrderCode(),
                'account_id' => $data['account_id'] ?? $sourceCart?->account_id ?? Auth::id(),
                'session_id' => $data['session_id'] ?? $sourceCart?->session_id,
                'receiver_name' => $data['receiver_name'] ?? $sourceCart?->account?->name,
                'receiver_phone' => $data['receiver_phone'] ?? null,
                'receiver_email' => $data['receiver_email'] ?? null,
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_province_id' => isset($data['shipping_province_id']) ? (string) $data['shipping_province_id'] : null,
                'shipping_district_id' => isset($data['shipping_district_id']) ? (string) $data['shipping_district_id'] : null,
                'shipping_ward_id' => isset($data['shipping_ward_id']) ? (string) $data['shipping_ward_id'] : null,
                'payment_method' => $data['payment_method'] ?? 'cod',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'transaction_code' => $data['transaction_code'] ?? null,
                'shipping_partner' => $data['shipping_partner'] ?? 'viettelpost',
                'delivery_status' => $data['delivery_status'] ?? 'pending',
                'status' => $data['status'] ?? 'pending',
                'total_price' => $subtotal,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'discount' => $discount,
                'voucher_discount' => $voucherDiscount,
                'voucher_code' => $data['voucher_code'] ?? null,
                'final_price' => $finalPrice,
                'is_flash_sale' => $hasFlashSale ? 1 : 0,
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
            ];

            if ($order) {
                $order->update($payload);
                $order->items()->delete();
            } else {
                $order = Order::create($payload);
            }

            /** @var \App\Models\Account|null $actor */
            $actor = Auth::user();

            foreach ($items as $row) {
                /** @var Product|null $product */
                $product = $products[$row['product_id']] ?? null;
                if (! $product) {
                    continue;
                }

                $isFlashSale = $product->isInFlashSale();
                $flashSaleItem = $isFlashSale ? $product->currentFlashSaleItem()->first() : null;

                $variantId = $row['product_variant_id'] ?? null;
                $variant = $variantId && $variants->has($variantId) ? $variants[$variantId] : null;

                $orderItem = new OrderItem;
                $orderItem->order_id = $order->id;
                $orderItem->uuid = (string) Str::uuid();
                $orderItem->product_id = $product->id;
                $orderItem->product_variant_id = $variantId;
                $orderItem->is_flash_sale = $isFlashSale ? 1 : 0;
                $orderItem->flash_sale_item_id = $flashSaleItem?->id;
                $orderItem->quantity = $row['quantity'];
                $orderItem->price = $row['price'];
                $orderItem->total_price = $row['total_price'];
                $orderItem->save();

                // Trừ tồn kho từ variant hoặc product
                if ($variant && $variant->is_active) {
                    if ($variant->stock_quantity !== null) {
                        $deductQty = (int) $row['quantity'];
                        $currentStock = (int) $variant->stock_quantity;
                        $variant->stock_quantity = max(0, $currentStock - $deductQty);
                        $variant->save();
                    }
                } elseif (! is_null($product->stock_quantity)) {
                    $this->inventoryService->adjustStock(
                        $product,
                        -$row['quantity'],
                        'order',
                        $actor,
                        Order::class,
                        $order->id,
                        'Đặt hàng '.$order->code
                    );
                }

                if ($flashSaleItem) {
                    $flashSaleItem->reduceStock((int) $row['quantity']);
                }
            }

            return $order->fresh(['items.product']);
        });
    }

    protected function generateOrderCode(): string
    {
        $prefix = 'ADM-'.now()->format('ymd');

        do {
            $code = $prefix.'-'.Str::upper(Str::random(4));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
