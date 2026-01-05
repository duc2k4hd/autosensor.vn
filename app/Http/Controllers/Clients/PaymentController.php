<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Mail\OrderPaidMail;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Hiển thị trang thanh toán thành công.
     */
    public function return(Request $request)
    {
        Log::info('PayOS Return Callback', $request->all());

        $orderCode = $request->query('orderCode');
        $status = $request->query('status');
        $message = $request->query('message');

        $payment = Payment::with(['order.items.product', 'order.shippingAddress'])
            ->where('transaction_code', $orderCode)
            ->where('method', 'payos')
            ->first();

        if (! $payment || ! $payment->order) {
            return view('clients.pages.payment.cancel', [
                'orderCode' => $orderCode,
                'message' => 'Không tìm thấy thông tin đơn hàng. Vui lòng liên hệ AutoSensor Việt Nam để được hỗ trợ.',
            ]);
        }

        $order = $payment->order;
        $order->loadMissing(['billingAddress', 'payments']);

        if ($status !== 'PAID') {
            return view('clients.pages.payment.cancel', [
                'orderCode' => $order->code,
                'message' => $message ?? 'Thanh toán chưa hoàn tất. Vui lòng thử lại hoặc chọn phương thức khác.',
                'order' => $order,
            ]);
        }

        // ============================
        // Đồng bộ trạng thái khi return (fallback khi webhook chưa cấu hình)
        // ============================
        try {
            // Cập nhật payment nếu chưa success
            if ($payment->status !== 'success') {
                $raw = $payment->raw_response ?? [];
                if (! is_array($raw)) {
                    $raw = [];
                }

                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'raw_response' => array_merge($raw, [
                        'return_callback' => $request->all(),
                        'return_synced_at' => now()->toISOString(),
                    ]),
                ]);
            }

            // Cập nhật đơn hàng nếu chưa đánh dấu đã thanh toán
            $wasPaid = $order->payment_status === 'paid';
            if (! $wasPaid) {
                // Khi thanh toán thành công, luôn set status = 'processing'
                // Đảm bảo logic đồng nhất với PayOSService->handlePaymentSuccess()
                // (PayOSService luôn set status = 'processing' khi thanh toán thành công)
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);
            }

            $order->refresh();

            // Gửi email xác nhận thanh toán cho khách hàng (chỉ gửi 1 lần khi thanh toán thành công)
            if (! $wasPaid) {
                try {
                    if ($order->receiver_email || $order->account?->email) {
                        Mail::to($order->receiver_email ?? $order->account->email)
                            ->send(new OrderPaidMail($order->fresh(['items.product', 'items.variant'])));
                    }
                } catch (\Throwable $e) {
                    // Log lỗi nhưng không làm gián đoạn flow
                    Log::warning('Failed to send order paid email', [
                        'order_id' => $order->id,
                        'email' => $order->receiver_email ?? $order->account?->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to sync order/payment status on PayOS return', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);
        }

        // Lấy tên địa chỉ từ ID nếu không có shippingAddress
        $addressNames = $this->getAddressNamesFromIds(
            $order->shipping_province_id,
            $order->shipping_district_id,
            $order->shipping_ward_id
        );

        return view('clients.pages.payment.success', [
            'order' => $order,
            'payment' => $payment,
            'message' => $message ?? 'Thanh toán thành công! Đơn hàng của bạn đang được xử lý.',
            'addressNames' => $addressNames,
        ]);
    }

    /**
     * Trang hủy thanh toán.
     * Khi hủy thanh toán, sẽ hủy luôn đơn hàng và payment để tránh rác dữ liệu.
     */
    public function cancel(Request $request)
    {
        Log::info('PayOS Cancel Callback', $request->all());

        $orderCode = $request->query('orderCode');
        $message = $request->query('message');

        $payment = Payment::with('order')
            ->where('transaction_code', $orderCode)
            ->where('method', 'payos')
            ->first();

        $order = $payment?->order;
        $orderCodeDisplay = $order?->code ?? $orderCode;

        // Nếu có đơn hàng và chưa hủy, hủy đơn hàng và payment
        if ($order && $order->status !== 'cancelled') {
            try {
                DB::beginTransaction();

                // Hủy đơn hàng (sẽ restore stock nếu cần)
                $this->orderService->cancelOrder(
                    $order,
                    'Khách hàng hủy thanh toán',
                    restoreStock: true
                );

                // Cập nhật tất cả payment liên quan thành cancelled
                $cancelNote = "\nHủy bởi khách hàng: ".now()->format('Y-m-d H:i:s');
                $order->payments()
                    ->where('status', '!=', 'success')
                    ->get()
                    ->each(function ($payment) use ($cancelNote) {
                        $payment->update([
                            'status' => 'cancelled',
                            'notes' => ($payment->notes ?? '').$cancelNote,
                        ]);
                    });

                DB::commit();

                Log::info('Order and payment cancelled after payment cancel', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'payment_id' => $payment?->id,
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Failed to cancel order and payment after payment cancel', [
                    'order_id' => $order->id,
                    'payment_id' => $payment?->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($payment && $payment->status !== 'cancelled' && $payment->status !== 'success') {
            // Nếu không có đơn hàng nhưng có payment, chỉ hủy payment
            try {
                $payment->update([
                    'status' => 'cancelled',
                    'notes' => ($payment->notes ?? '')."\nHủy bởi khách hàng: ".now()->format('Y-m-d H:i:s'),
                ]);

                Log::info('Payment cancelled after payment cancel', [
                    'payment_id' => $payment->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to cancel payment after payment cancel', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('clients.pages.payment.cancel', [
            'orderCode' => $orderCodeDisplay,
            'message' => $message ?? 'Bạn đã hủy thanh toán. Đơn hàng đã được hủy.',
            'order' => $order,
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
