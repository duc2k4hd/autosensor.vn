<?php

namespace App\Http\Controllers\Clients\APIs\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoucherApplyRequest;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class VoucherController extends Controller
{
    public function __construct(private VoucherService $voucherService) {}

    public function apply(VoucherApplyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $orderData = $validated['order_data'];
        $accountId = auth('web')->id();
        $sessionId = $request->session()->getId();

        try {
            $result = $this->voucherService->validate(
                $validated['voucher_code'],
                (float) $orderData['subtotal'],
                (float) ($orderData['shipping_fee'] ?? 0),
                $accountId,
                $sessionId
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Không thể áp dụng voucher lúc này. Vui lòng thử lại.',
            ], 500);
        }

        $request->session()->put('checkout.applied_voucher', [
            'code' => $result['voucher']->code,
            'discount' => $result['discount'],
            'product_discount' => $result['product_discount'],
            'shipping_discount' => $result['shipping_discount'],
            'shipping_fee' => $result['shipping_fee'],
            'original_shipping_fee' => $result['original_shipping_fee'],
            'total' => $result['total'],
            'subtotal' => (float) $orderData['subtotal'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng voucher thành công.',
            'data' => [
                'voucher' => [
                    'code' => $result['voucher']->code,
                    'name' => $result['voucher']->name,
                    'type' => $result['voucher']->type,
                ],
                'discount' => $result['discount'],
                'summary' => [
                    'subtotal' => (float) $orderData['subtotal'],
                    'shipping_fee' => $result['shipping_fee'],
                    'original_shipping_fee' => $result['original_shipping_fee'],
                    'total' => $result['total'],
                ],
            ],
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->session()->forget('checkout.applied_voucher');

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy voucher.',
        ]);
    }
}
