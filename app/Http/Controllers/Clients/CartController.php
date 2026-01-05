<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->getCart($request, withItems: true);

        if ($cart) {
            $cart->items->loadMissing(['product.currentFlashSaleItem.flashSale', 'variant']);
            $cart->items->each->syncPrice();
            $cart->setRelation('cartItems', $cart->items);

            Product::preloadImages(
                $cart->items->pluck('product')->filter()
            );
        }

        $productNew = Cache::remember('new_products', 3600, function () {
            $products = Product::active()
                ->orderBy('created_at', 'desc')
                ->inRandomOrder()
                ->limit(9)
                ->get() ?? collect();

            Product::preloadImages($products);

            return $products;
        });

        Product::preloadImages($productNew);

        return view('clients.pages.cart.index', [
            'account' => auth('web')->user(),
            'cart' => $cart,
            'cartItems' => $cart?->items ?? collect(),
            'cartTotal' => $cart?->items?->sum(fn ($item) => $item->subtotal) ?? 0,
            'productNew' => $productNew,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = $data['quantity'] ?? 1;

        $product = Product::active()
            ->with('currentFlashSaleItem.flashSale')
            ->findOrFail($data['product_id']);

        $variant = null;
        if (! empty($data['product_variant_id'])) {
            $variant = \App\Models\ProductVariant::where('id', $data['product_variant_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Biến thể không tồn tại hoặc không thuộc về sản phẩm này.',
                ]);
            }

            if ($variant->stock_quantity !== null && $variant->stock_quantity < 1) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Biến thể này đã hết hàng.',
                ]);
            }
        } else {
            // Nếu sản phẩm có variants nhưng không chọn variant
            if ($product->hasVariants()) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Vui lòng chọn biến thể sản phẩm.',
                ]);
            }

            if ($product->stock_quantity !== null && $product->stock_quantity < 1) {
                throw ValidationException::withMessages([
                    'product_id' => 'Sản phẩm đã hết hàng.',
                ]);
            }
        }

        $result = $this->addProductToCart($request, $product, $quantity, $variant);

        if ($result['added_quantity'] <= 0) {
            $remaining = null;
            if ($variant) {
                $remaining = $variant->stock_quantity !== null ? max(0, (int) $variant->stock_quantity - (int) $result['current_quantity']) : null;
            } else {
                $remaining = $product->stock_quantity !== null ? max(0, (int) $product->stock_quantity - (int) $result['current_quantity']) : null;
            }

            $message = 'Bạn đã thêm tối đa số lượng sản phẩm này vào giỏ hàng.';
            if ($remaining !== null) {
                $message = $remaining > 0
                    ? "Chỉ còn lại tối đa {$remaining} sản phẩm có thể thêm vào giỏ."
                    : 'Không thể thêm thêm vì đã hết tồn kho cho sản phẩm này.';
            }

            return redirect()
                ->back()
                ->with('warning', $message);
        }

        return redirect()
            ->back()
            ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function addAccessory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = $data['quantity'] ?? 1;

        $product = Product::active()
            ->with('currentFlashSaleItem.flashSale')
            ->findOrFail($data['product_id']);

        $variant = null;
        if (! empty($data['product_variant_id'])) {
            $variant = \App\Models\ProductVariant::where('id', $data['product_variant_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Biến thể không tồn tại hoặc không thuộc về sản phẩm này.',
                ]);
            }

            if ($variant->stock_quantity !== null && $variant->stock_quantity < 1) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Biến thể này đã hết hàng.',
                ]);
            }
        } else {
            if ($product->hasVariants()) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Vui lòng chọn biến thể sản phẩm.',
                ]);
            }

            if ($product->stock_quantity !== null && $product->stock_quantity < 1) {
                throw ValidationException::withMessages([
                    'product_id' => 'Sản phẩm đã hết hàng.',
                ]);
            }
        }

        $result = $this->addProductToCart($request, $product, $quantity, $variant);

        if ($result['added_quantity'] <= 0) {
            // Lấy stock hiện tại (có thể đã thay đổi sau khi thêm)
            $currentStock = null;
            if ($variant) {
                $currentStock = $variant->fresh()->stock_quantity;
            } else {
                $currentStock = $product->fresh()->stock_quantity;
            }

            // Tính remaining dựa trên stock hiện tại và số lượng đã có trong giỏ
            $remaining = null;
            if ($currentStock !== null) {
                // Nếu đã có trong giỏ, remaining = stock - số lượng đã có
                // Nếu chưa có trong giỏ, remaining = stock
                $remaining = max(0, (int) $currentStock - (int) $result['current_quantity']);
            }

            // Kiểm tra flash sale limit
            $maxPerUser = $product->flashSaleLimitPerUser();
            if ($maxPerUser && $result['current_quantity'] >= $maxPerUser) {
                $message = "Bạn chỉ có thể mua tối đa {$maxPerUser} sản phẩm này trong chương trình Flash Sale.";
            } elseif ($remaining !== null) {
                if ($remaining <= 0) {
                    $message = 'Không thể thêm thêm vì đã hết tồn kho cho sản phẩm này.';
                } else {
                    $message = "Chỉ còn lại tối đa {$remaining} sản phẩm có thể thêm vào giỏ.";
                }
            } else {
                $message = 'Bạn đã thêm tối đa số lượng sản phẩm này vào giỏ hàng.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'cart_total_items' => $result['cart_total_items'],
                'cart_total_amount' => $result['cart_total_amount'],
            ], 422);
        }

        Product::preloadImages(collect([$product]));

        $cartItem = $result['cart_item'];

        // Lấy giá từ variant hoặc product
        $displayPrice = $variant ? (float) $variant->display_price : $product->resolveCartPrice();

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm sản phẩm đi kèm vào giỏ hàng.',
            'cart_total_items' => $result['cart_total_items'],
            'cart_total_amount' => $result['cart_total_amount'],
            'cart_item' => [
                'id' => $cartItem?->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $result['current_quantity'],
                'price' => $displayPrice,
                'thumbnail' => asset('clients/assets/img/clothes/'.($product->primaryImage->url ?? 'no-image.webp')),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $cart = $this->getCart($request, withItems: true);

        if (! $cart) {
            return redirect()->route('client.cart.index')
                ->with('warning', 'Giỏ hàng của bạn hiện đang trống.');
        }

        $quantities = $request->input('items', []);

        // Debug log
        Log::info('Cart update request', [
            'all_input' => $request->all(),
            'items' => $quantities,
            'items_type' => gettype($quantities),
            'items_count' => is_array($quantities) ? count($quantities) : 0,
        ]);

        if (! is_array($quantities) || empty($quantities)) {
            Log::warning('Cart update: No items in request', [
                'quantities' => $quantities,
                'all_input' => $request->all(),
            ]);

            return redirect()->route('client.cart.index')
                ->with('info', 'Không có thay đổi nào được gửi lên.');
        }

        DB::transaction(function () use ($cart, $quantities) {
            $cart->items->loadMissing(['product.currentFlashSaleItem.flashSale', 'variant']);

            foreach ($cart->items as $item) {
                // Try both string and integer keys
                $itemId = $item->id;
                $quantityValue = null;

                if (array_key_exists($itemId, $quantities)) {
                    $quantityValue = $quantities[$itemId];
                } elseif (array_key_exists((string) $itemId, $quantities)) {
                    $quantityValue = $quantities[(string) $itemId];
                }

                if ($quantityValue === null) {
                    Log::debug('Cart update: Item not in request', [
                        'item_id' => $itemId,
                        'available_keys' => array_keys($quantities),
                    ]);

                    continue;
                }

                $requestedQuantity = (int) $quantityValue;
                $requestedQuantity = max($requestedQuantity, 0);

                Log::debug('Cart update: Processing item', [
                    'item_id' => $itemId,
                    'requested_quantity' => $requestedQuantity,
                    'current_quantity' => $item->quantity,
                ]);

                $product = $item->product;
                $variant = $item->variant;

                if (! $product) {
                    $item->delete();

                    continue;
                }

                // Kiểm tra variant có thuộc về product không
                if ($variant && $variant->product_id !== $product->id) {
                    $item->delete();

                    continue;
                }

                // Lấy giá và tồn kho từ variant hoặc product
                if ($variant && $variant->is_active) {
                    $unitPrice = (float) $variant->display_price;
                    $availableStock = $variant->stock_quantity;
                } else {
                    $unitPrice = $product->resolveCartPrice();
                    $availableStock = $product->stock_quantity;
                }

                $maxPerUser = $product->flashSaleLimitPerUser();

                if ($maxPerUser && $requestedQuantity > $maxPerUser) {
                    $requestedQuantity = $maxPerUser;
                }

                if (! is_null($availableStock) && $requestedQuantity > $availableStock) {
                    $requestedQuantity = $availableStock;
                }

                if ($requestedQuantity <= 0) {
                    $item->delete();

                    continue;
                }

                $item->update([
                    'quantity' => $requestedQuantity,
                    'price' => $unitPrice,
                ]);

                Log::debug('Cart update: Item updated', [
                    'item_id' => $item->id,
                    'old_quantity' => $item->getOriginal('quantity'),
                    'new_quantity' => $requestedQuantity,
                    'saved_quantity' => $item->fresh()->quantity,
                ]);
            }
        });

        // Reload cart to ensure fresh data
        $cart->refresh();
        $cart->load('items.product', 'items.variant');

        return redirect()
            ->route('client.cart.index')
            ->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function removeItem(Request $request, string $cartItem)
    {
        if (! ctype_digit($cartItem)) {
            return redirect()->route('client.cart.index')
                ->with('warning', 'Yêu cầu không hợp lệ.');
        }

        $cartItemId = (int) $cartItem;

        $cart = $this->getCart($request, withItems: true);

        if (! $cart) {
            return redirect()->route('client.cart.index')
                ->with('info', 'Giỏ hàng của bạn đang trống.');
        }

        $item = $cart->items->firstWhere('id', $cartItemId);

        if (! $item) {
            return redirect()->route('client.cart.index')
                ->with('warning', 'Không tìm thấy sản phẩm trong giỏ hàng.');
        }

        $item->delete();

        return redirect()->route('client.cart.index')
            ->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function removeAll(Request $request)
    {
        $cart = $this->getCart($request, withItems: true);

        if (! $cart) {
            return redirect()->route('client.cart.index')
                ->with('info', 'Giỏ hàng của bạn đang trống.');
        }

        $cart->items()->delete();
        $cart->update([
            'quantity' => 0,
        ]);

        return redirect()->route('client.cart.index')
            ->with('success', 'Đã xóa toàn bộ sản phẩm trong giỏ hàng.');
    }

    protected function getCart(Request $request, bool $withItems = false, bool $createIfMissing = false, ?int $seedProductId = null): ?Cart
    {
        $accountId = auth('web')->id();
        $sessionId = $request->session()->getId();

        $query = Cart::query();

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id')->where('session_id', $sessionId);
        }

        if ($withItems) {
            $query->with([
                'items.product.currentFlashSaleItem.flashSale',
                'items.variant',
            ]);
        }

        $cart = $query->latest('id')->first();

        if (! $cart && $createIfMissing) {
            $cart = Cart::create([
                'account_id' => $accountId,
                'session_id' => $accountId ? null : $sessionId,
                'product_id' => $seedProductId ?? 0,
                'quantity' => 0,
            ]);
        } elseif ($cart && $seedProductId && ! $cart->product_id) {
            $cart->update(['product_id' => $seedProductId]);
        }

        return $cart?->loadMissing('items');
    }

    protected function addProductToCart(Request $request, Product $product, int $quantity = 1, ?\App\Models\ProductVariant $variant = null): array
    {
        $cart = $this->getCart($request, createIfMissing: true, seedProductId: $product->id);

        $result = [
            'added_quantity' => 0,
            'current_quantity' => 0,
            'cart_item' => null,
        ];

        DB::transaction(function () use ($cart, $product, $quantity, $variant, &$result) {
            // Tìm cart item với cùng product_id và variant_id
            $query = $cart->items()->where('product_id', $product->id);
            if ($variant) {
                $query->where('product_variant_id', $variant->id);
            } else {
                $query->whereNull('product_variant_id');
            }
            $cartItem = $query->lockForUpdate()->first();

            // Lấy giá từ variant hoặc product
            if ($variant) {
                $unitPrice = (float) $variant->display_price;
                $availableStock = $variant->stock_quantity;
            } else {
                $unitPrice = $product->resolveCartPrice();
                $availableStock = $product->stock_quantity;
            }

            $maxPerUser = $product->flashSaleLimitPerUser();

            $isFlashSale = $product->isInFlashSale();
            $flashSaleItem = $isFlashSale ? $product->currentFlashSaleItem()->first() : null;

            $originalQuantity = $cartItem ? $cartItem->quantity : 0;
            $newQuantity = $originalQuantity + $quantity;

            if ($maxPerUser && $newQuantity > $maxPerUser) {
                $newQuantity = $maxPerUser;
            }

            if ($availableStock !== null && $newQuantity > $availableStock) {
                $newQuantity = $availableStock;
            }

            if ($cartItem) {
                $cartItem->update([
                    'quantity' => $newQuantity,
                    'price' => $unitPrice,
                    'is_flash_sale' => $isFlashSale,
                    'flash_sale_item_id' => $flashSaleItem?->id,
                ]);
            } else {
                $cartItem = $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $newQuantity,
                    'price' => $unitPrice,
                    'is_flash_sale' => $isFlashSale,
                    'flash_sale_item_id' => $flashSaleItem?->id,
                    'uuid' => (string) Str::uuid(),
                ]);
            }

            $result['added_quantity'] = max($newQuantity - $originalQuantity, 0);
            $result['current_quantity'] = $newQuantity;
            $result['cart_item'] = $cartItem;
        });

        $cart->loadMissing('items.product');

        $cartItem = $cart->items->firstWhere('product_id', $product->id);

        return [
            'cart' => $cart,
            'product' => $product,
            'cart_item' => $cartItem,
            'cart_total_items' => $cart->items->sum(fn ($item) => $item->quantity),
            'cart_total_amount' => $cart->items->sum(fn ($item) => $item->subtotal),
            'added_quantity' => $result['added_quantity'],
            'current_quantity' => $result['current_quantity'],
        ];
    }
}
