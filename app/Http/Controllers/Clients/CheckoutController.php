<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Mail\OrderCreatedMail;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\NotificationService;
use App\Services\PayOSService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private VoucherService $voucherService,
        private PayOSService $payOSService,
        private NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $cart = $this->resolveCart($request, withItems: true);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('client.cart.index')
                ->with('warning', 'Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm trước khi thanh toán.');
        }

        $cartItems = $cart->items;

        $cartItems->each->syncPrice();
        $cartItems->loadMissing(['product', 'variant']);

        Product::preloadImages(
            $cartItems->pluck('product')->filter()
        );

        $addresses = $this->getAccountAddresses();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        $cartSubtotal = $cartItems->sum(fn ($item) => $item->subtotal);

        return view('clients.pages.checkout.index', [
            'cart' => $cart,
            'cartItems' => $cartItems,
            'cartSubtotal' => $cartSubtotal,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'appliedVoucher' => $request->session()->get('checkout.applied_voucher'),
        ]);
    }

    public function store(Request $request)
    {
        $cart = $this->resolveCart($request, withItems: true);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('client.cart.index')
                ->with('warning', 'Giỏ hàng trống, không thể tạo đơn hàng.');
        }

        $data = $request->validate([
            'fullname' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:120'],
            'district' => ['required', 'string', 'max:120'],
            'ward' => ['required', 'string', 'max:120'],
            'provinceId' => ['required', 'integer'],
            'districtId' => ['required', 'integer'],
            'wardId' => ['required', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'shipping_fee_original' => ['required', 'numeric', 'min:1'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'serviceId' => ['nullable', 'integer'],
            'serviceTypeId' => ['nullable', 'integer'],
            'shipping_label' => ['nullable', 'string', 'max:150'],
            'payment' => ['required', 'in:cod,bank_transfer'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'voucher_discount' => ['nullable', 'numeric', 'min:0'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ], [
            'fullname.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'province.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'district.required' => 'Vui lòng chọn Quận/Huyện.',
            'ward.required' => 'Vui lòng chọn Phường/Xã.',
            'provinceId.required' => 'Thiếu ID tỉnh/thành.',
            'districtId.required' => 'Thiếu ID quận/huyện.',
            'wardId.required' => 'Thiếu ID phường/xã.',
            'shipping_fee.required' => 'Vui lòng chọn phương thức giao hàng.',
            'payment.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        $totalGross = 0;
        $cart->loadMissing(['items.product', 'items.variant']);

        $cart->items->each(function ($item) use (&$totalGross) {
            $item->syncPrice();
            $totalGross += ($item->price ?? 0) * ($item->quantity ?? 0);
        });

        $subtotal = $totalGross;
        $discount = 0;
        $shippingFeeOriginal = (float) $data['shipping_fee_original'];
        $shippingFee = (float) $data['shipping_fee'];
        $sessionId = $request->session()->getId();
        $voucherSummary = null;

        $account = Auth::guard('web')->user();
        $voucherCode = $request->input('voucher_code');

        if ($voucherCode) {
            try {
                $voucherSummary = $this->voucherService->validate(
                    $voucherCode,
                    $subtotal,
                    $shippingFeeOriginal,
                    $account?->id,
                    $sessionId
                );
                $discount = $voucherSummary['discount'];
                $shippingFee = $voucherSummary['shipping_fee'];
            } catch (ValidationException $e) {
                return back()
                    ->withInput()
                    ->withErrors(['voucher_code' => $e->getMessage()]);
            }
        }

        $total = $voucherSummary['total'] ?? ($subtotal - $discount + $shippingFee);

        try {
            DB::beginTransaction();
            $addressPayload = [
                'account_id' => $account?->id,
                'full_name' => $data['fullname'],
                'phone_number' => $data['phone'],
                'detail_address' => $data['address'],
                'province' => $data['province'],
                'district' => $data['district'],
                'ward' => $data['ward'],
                'province_code' => (string) ($data['provinceId'] ?? ''),
                'district_code' => (string) ($data['districtId'] ?? ''),
                'ward_code' => (string) ($data['wardId'] ?? ''),
                'postal_code' => $data['postal_code'] ?? '00000',
                'country' => $data['country'] ?? 'Việt Nam',
                'address_type' => 'shipping',
                'notes' => $request->input('customer_note'),
                'is_default' => false,
            ];

            if ($account) {
                $shippingAddress = Address::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'detail_address' => $data['address'],
                        'district_code' => $data['districtId'],
                        'ward_code' => $data['wardId'],
                    ],
                    $addressPayload
                );
            } else {
                $shippingAddress = Address::create($addressPayload);
            }

            // Lấy affiliate code từ cookie
            $affiliateCode = $request->cookie('affiliate_ref');

            // Tạo đơn hàng theo schema mới (orders.total_price / final_price ...)
            $order = Order::create([
                'code' => $this->generateOrderCode(),
                'account_id' => $account?->id,
                'session_id' => $account ? null : $request->session()->getId(),
                'shipping_address_id' => $shippingAddress->id,
                'total_price' => $subtotal,
                'shipping_fee' => $shippingFee,
                'tax' => 0,
                'discount' => 0,
                'voucher_id' => $voucherSummary['voucher']->id ?? null,
                'voucher_code' => $voucherCode ?: null,
                'voucher_discount' => $discount,
                'affiliate_code' => $affiliateCode,
                'final_price' => $total,
                'receiver_name' => $data['fullname'],
                'receiver_phone' => $data['phone'],
                'receiver_email' => $data['email'],
                'shipping_address' => $data['address'],
                'shipping_province_id' => (string) ($data['provinceId'] ?? ''),
                'shipping_district_id' => (string) ($data['districtId'] ?? ''),
                'shipping_ward_id' => (string) ($data['wardId'] ?? ''),
                'payment_method' => $data['payment'],
                'payment_status' => 'pending',
                'shipping_partner' => 'ghn',
                'customer_note' => $data['customer_note'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_variant_id' => $item->product_variant_id ?? null,
                    'uuid' => (string) Str::uuid(),
                    'is_flash_sale' => (bool) ($item->is_flash_sale ?? false),
                    'flash_sale_item_id' => $item->flash_sale_item_id ?? null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->subtotal,
                    'options' => $item->options,
                ]);

                // Trừ tồn kho từ variant hoặc product
                $variant = $item->variant;
                if ($variant && $variant->is_active) {
                    if ($variant->stock_quantity !== null) {
                        $deductQty = (int) $item->quantity;
                        $currentStock = (int) $variant->stock_quantity;
                        $variant->stock_quantity = max(0, $currentStock - $deductQty);
                        $variant->save();
                    }
                } elseif ($product && $product->stock_quantity !== null) {
                    $product->decrement('stock_quantity', $item->quantity);
                }

                $flashSaleItem = $product?->currentFlashSaleItem;
                if ($flashSaleItem) {
                    $flashSaleItem->reduceStock((int) $item->quantity);
                }
            }

            $cart->items()->delete();

            if ($voucherSummary) {
                $this->voucherService->recordUsage(
                    $voucherSummary['voucher'],
                    $order->id,
                    $account?->id,
                    $sessionId,
                    $discount,
                    $request->ip()
                );
                $request->session()->forget('checkout.applied_voucher');
            }

            DB::commit();

            // Gửi thông báo đơn hàng mới cho admin
            $this->notificationService->notifyNewOrder(
                $order->id,
                $order->code,
                (float) $total
            );

            // Nếu thanh toán bằng chuyển khoản, tạo link PayOS trước khi gửi email
            $checkoutUrl = null;
            if ($data['payment'] === 'bank_transfer') {
                if ((float) $total < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Số tiền thanh toán không hợp lệ. Vui lòng kiểm tra lại giỏ hàng hoặc chọn phương thức khác.',
                    ], 422);
                }

                $paymentResult = $this->payOSService->createPaymentLink($order);

                if ($paymentResult['success']) {
                    $checkoutUrl = $paymentResult['checkout_url'];
                } else {
                    // Nếu tạo link thất bại, rollback và báo lỗi
                    DB::rollBack(); // Rollback transaction vì không thể thanh toán

                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo link thanh toán. Vui lòng thử lại sau.',
                        'error' => $paymentResult['error'] ?? 'Unknown error',
                    ], 500);
                }
            }

            // Gửi email xác nhận đơn hàng cho khách hàng (có kèm link thanh toán nếu là bank_transfer)
            try {
                if ($order->receiver_email || $order->account?->email) {
                    Mail::to($order->receiver_email ?? $order->account->email)
                        ->send(new OrderCreatedMail($order->fresh(['items.product', 'items.variant']), $checkoutUrl));
                }
            } catch (\Throwable $e) {
                // Log lỗi nhưng không làm gián đoạn flow
                Log::warning('Failed to send order created email', [
                    'order_id' => $order->id,
                    'email' => $order->receiver_email ?? $order->account?->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // Nếu thanh toán bằng chuyển khoản, trả về JSON với checkout_url
            if ($data['payment'] === 'bank_transfer') {
                return response()->json([
                    'success' => true,
                    'payment_method' => 'bank_transfer',
                    'checkout_url' => $checkoutUrl,
                ]);
            }

            // Nếu là COD, chuyển hướng như cũ
            return response()->json([
                'success' => true,
                'payment_method' => 'cod',
                'redirect_url' => route('client.orders.show', $order->code),
                'message' => 'Đặt hàng thành công! Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Checkout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo đơn hàng. Vui lòng thử lại.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function resolveCart(Request $request, bool $withItems = false, bool $createIfMissing = false): ?Cart
    {
        $accountId = Auth::guard('web')->id();
        $sessionId = $request->session()->getId();

        $query = Cart::query();

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id')->where('session_id', $sessionId);
        }

        if ($withItems) {
            $query->with([
                'items.product',
                'items.variant',
            ]);
        }

        $cart = $query->latest('id')->first();

        if (! $cart && $createIfMissing) {
            $cart = Cart::create([
                'account_id' => $accountId,
                'session_id' => $accountId ? null : $sessionId,
            ]);
        }

        return $cart;
    }

    protected function getAccountAddresses(): Collection
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return collect();
        }

        return $user->addresses()->latest()->get();
    }

    protected function generateOrderCode(): string
    {
        $prefix = 'XWG-'.now()->format('ymd');

        do {
            $code = $prefix.'-'.Str::upper(Str::random(4));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
