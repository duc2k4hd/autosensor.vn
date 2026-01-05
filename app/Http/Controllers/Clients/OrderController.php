<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Show an order detail page for the authenticated client.
     */
    public function show(Request $request, string $code): View|RedirectResponse
    {
        $accountId = auth('web')->id();

        if (! $accountId) {
            return redirect()->route('client.auth.login')
                ->with('error', 'Vui lòng đăng nhập để xem đơn hàng.');
        }

        $order = Order::query()
            ->with([
                'items.product',
                'shippingAddress',
                'billingAddress',
                'payments',
            ])
            ->where('code', $code)
            ->where('account_id', $accountId)
            ->firstOrFail();

        Product::preloadImages(
            $order->items->pluck('product')->filter()
        );

        $statusFlow = [
            ['key' => 'pending', 'label' => 'Chờ xác nhận', 'description' => 'Đơn hàng đã được ghi nhận tại AutoSensor Việt Nam.'],
            ['key' => 'confirmed', 'label' => 'Đang xử lý', 'description' => 'Đội ngũ đang chuẩn bị hàng và đóng gói.'],
            ['key' => 'shipping', 'label' => 'Đang vận chuyển', 'description' => 'Đã bàn giao cho đơn vị vận chuyển.'],
            ['key' => 'completed', 'label' => 'Hoàn tất', 'description' => 'Đơn hàng đã được giao thành công.'],
        ];

        $statusAliases = [
            'pending' => 'pending',
            'awaiting_payment' => 'pending',
            'processing' => 'confirmed',
            'confirmed' => 'confirmed',
            'packed' => 'confirmed',
            'shipping' => 'shipping',
            'shipped' => 'shipping',
            'delivered' => 'completed',
            'completed' => 'completed',
        ];

        $normalizedStatus = $statusAliases[$order->status] ?? $order->status ?? 'pending';
        $currentStatusIndex = collect($statusFlow)->pluck('key')->search($normalizedStatus);

        if ($currentStatusIndex === false) {
            $currentStatusIndex = $normalizedStatus === 'cancelled' ? -1 : 0;
        }

        // Lấy tên địa chỉ từ ID nếu không có shippingAddress
        $addressNames = $this->getAddressNamesFromIds(
            $order->shipping_province_id,
            $order->shipping_district_id,
            $order->shipping_ward_id
        );

        // Lấy checkout_url từ Payment nếu thanh toán bằng bank_transfer và chưa thanh toán
        $checkoutUrl = null;
        if ($order->payment_method === 'bank_transfer' && $order->payment_status === 'pending') {
            $payment = $order->payments()
                ->where('method', 'payos')
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($payment && $payment->raw_response) {
                $rawResponse = is_array($payment->raw_response)
                    ? $payment->raw_response
                    : (is_string($payment->raw_response) ? json_decode($payment->raw_response, true) : []);

                if (is_array($rawResponse)) {
                    $checkoutUrl = $rawResponse['data']['checkoutUrl'] ?? $rawResponse['checkout_url'] ?? null;
                }
            }
        }

        return view('clients.pages.order.detail', [
            'order' => $order,
            'statusFlow' => $statusFlow,
            'currentStatusIndex' => $currentStatusIndex,
            'normalizedStatus' => $normalizedStatus,
            'addressNames' => $addressNames,
            'checkoutUrl' => $checkoutUrl,
        ]);
    }

    /**
     * Hiển thị danh sách đơn hàng của khách hàng.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $accountId = auth('web')->id();

        if (! $accountId) {
            return redirect()->route('client.auth.login')
                ->with('error', 'Vui lòng đăng nhập để xem đơn hàng.');
        }

        $query = Order::query()
            ->with(['items.product.primaryImage'])
            ->where('account_id', $accountId)
            ->latest('created_at');

        $filters = [
            'status' => $request->get('status', ''),
            'payment_status' => $request->get('payment_status', ''),
        ];

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['payment_status']) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $orders = $query->with(['items.product', 'items.variant.primaryVariantImage'])->paginate(10);

        Product::preloadImages(
            $orders->getCollection()->flatMap(fn ($order) => $order->items->pluck('product'))->filter()
        );

        return view('clients.pages.order.index', compact('orders', 'filters'));
    }

    /**
     * Hủy đơn hàng (chỉ khi status là pending).
     */
    public function cancel(Request $request, string $code): RedirectResponse
    {
        $accountId = auth('web')->id();

        if (! $accountId) {
            return redirect()->route('client.auth.login')
                ->with('error', 'Vui lòng đăng nhập.');
        }

        $order = Order::where('code', $code)
            ->where('account_id', $accountId)
            ->with(['items.product', 'items.variant.primaryVariantImage'])
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->back()
                ->with('error', 'Không thể hủy đơn hàng đã thanh toán. Vui lòng liên hệ hỗ trợ.');
        }

        $order->status = 'cancelled';
        $order->save();

        return redirect()->back()
            ->with('success', 'Đã hủy đơn hàng thành công.');
    }

    /**
     * Mua lại đơn hàng (thêm các sản phẩm vào giỏ hàng).
     */
    public function reorder(Request $request, string $code): RedirectResponse
    {
        $accountId = auth('web')->id();

        if (! $accountId) {
            return redirect()->route('client.auth.login')
                ->with('error', 'Vui lòng đăng nhập.');
        }

        $order = Order::where('code', $code)
            ->where('account_id', $accountId)
            ->with(['items.product', 'items.variant'])
            ->firstOrFail();

        // Thêm các sản phẩm vào giỏ hàng
        $cartController = app(\App\Http\Controllers\Clients\CartController::class);

        foreach ($order->items as $item) {
            if ($item->product && $item->product->active) {
                $cartRequest = new Request([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                ]);
                $cartRequest->setUserResolver(fn () => auth('web')->user());

                try {
                    $cartController->store($cartRequest);
                } catch (\Exception $e) {
                    Log::warning('Reorder: Failed to add product to cart', [
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('client.cart.index')
            ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    /**
     * Tra cứu vận đơn GHN.
     */
    public function track(Request $request, ?string $tracking_code = null): View
    {
        $trackingCode = $tracking_code ?? $request->get('tracking_code');

        return view('clients.pages.order.track', [
            'tracking_code' => $trackingCode,
        ]);
    }

    /**
     * In hóa đơn.
     */
    public function invoice(string $code): View
    {
        $accountId = auth('web')->id();

        if (! $accountId) {
            abort(403, 'Unauthorized');
        }

        $order = Order::query()
            ->with([
                'items.product',
                'shippingAddress',
                'billingAddress',
                'payments',
            ])
            ->where('code', $code)
            ->where('account_id', $accountId)
            ->firstOrFail();

        Product::preloadImages(
            $order->items->pluck('product')->filter()
        );

        // Lấy tên địa chỉ từ ID nếu không có shippingAddress
        $addressNames = $this->getAddressNamesFromIds(
            $order->shipping_province_id,
            $order->shipping_district_id,
            $order->shipping_ward_id
        );

        return view('clients.pages.order.invoice', [
            'order' => $order,
            'addressNames' => $addressNames,
        ]);
    }

    /**
     * Lấy tên địa chỉ từ ID bằng cách gọi GHN API.
     */
    protected function getAddressNamesFromIds(?int $provinceId, ?int $districtId, ?string $wardId): array
    {
        $result = [
            'province' => null,
            'district' => null,
            'ward' => null,
        ];

        if (! $provinceId && ! $districtId && ! $wardId) {
            return $result;
        }

        $baseUrl = config('services.ghn.base_url');
        $token = config('services.ghn.token');

        if (! $baseUrl || ! $token) {
            return $result;
        }

        try {
            // Lấy tên tỉnh/thành
            if ($provinceId) {
                $provinceResponse = Http::withHeaders([
                    'Token' => $token,
                    'Content-Type' => 'application/json',
                ])->timeout(3)->get($baseUrl.'master-data/province');

                if ($provinceResponse->successful()) {
                    $provinces = $provinceResponse->json('data', []);
                    $province = collect($provinces)->firstWhere('ProvinceID', $provinceId);
                    $result['province'] = $province['ProvinceName'] ?? null;
                }
            }

            // Lấy tên quận/huyện
            if ($districtId && $provinceId) {
                $districtResponse = Http::withHeaders([
                    'token' => $token,
                    'Content-Type' => 'application/json',
                ])->timeout(3)->post($baseUrl.'master-data/district', [
                    'province_id' => $provinceId,
                ]);

                if ($districtResponse->successful()) {
                    $districts = $districtResponse->json('data', []);
                    $district = collect($districts)->firstWhere('DistrictID', $districtId);
                    $result['district'] = $district['DistrictName'] ?? null;
                }
            }

            // Lấy tên phường/xã
            if ($wardId && $districtId) {
                $wardResponse = Http::withHeaders([
                    'token' => $token,
                    'Content-Type' => 'application/json',
                ])->timeout(3)->post($baseUrl.'master-data/ward', [
                    'district_id' => $districtId,
                ]);

                if ($wardResponse->successful()) {
                    $wards = $wardResponse->json('data', []);
                    $ward = collect($wards)->firstWhere('WardCode', (string) $wardId);
                    $result['ward'] = $ward['WardName'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            // Log lỗi nhưng không throw để không làm gián đoạn flow
            Log::warning('Failed to get address names from GHN API', [
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'ward_id' => $wardId,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }
}
