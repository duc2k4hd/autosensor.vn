<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherHistory;
use App\Models\VoucherUserUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * Validate voucher and calculate discount/summary.
     */
    public function validate(string $code, float $subtotal, float $shippingFee, ?int $accountId, string $sessionId): array
    {
        $voucher = Voucher::where('code', Str::upper(trim($code)))->first();

        if (! $voucher) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Mã voucher không tồn tại hoặc đã hết hạn.',
            ]);
        }

        if (! $voucher->isActive()) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Voucher này hiện không khả dụng.',
            ]);
        }

        if ($voucher->min_order_value && $subtotal < $voucher->min_order_value) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Giá trị đơn hàng chưa đạt mức tối thiểu để sử dụng voucher.',
            ]);
        }

        $this->ensureUsageAvailable($voucher, $accountId, $sessionId);

        [$productDiscount, $shippingDiscount, $shippingAfterDiscount] = $this->calculateDiscounts($voucher, $subtotal, $shippingFee);

        if ($productDiscount <= 0 && $shippingDiscount <= 0) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Voucher này không áp dụng cho đơn hàng hiện tại.',
            ]);
        }

        $totalDiscount = $productDiscount + $shippingDiscount;
        $total = max($subtotal - $productDiscount + $shippingAfterDiscount, 0);

        return [
            'voucher' => $voucher,
            'discount' => $totalDiscount,
            'product_discount' => $productDiscount,
            'shipping_discount' => $shippingDiscount,
            'shipping_fee' => $shippingAfterDiscount,
            'original_shipping_fee' => $shippingFee,
            'total' => $total,
        ];
    }

    protected function ensureUsageAvailable(Voucher $voucher, ?int $accountId, string $sessionId): void
    {
        if (! is_null($voucher->usage_limit)) {
            $used = VoucherHistory::where('voucher_id', $voucher->id)->count();

            if ($used >= $voucher->usage_limit) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher đã đạt số lượt sử dụng tối đa.',
                ]);
            }
        }

        if (! is_null($voucher->usage_limit_per_user)) {
            $usage = VoucherUserUsage::where('voucher_id', $voucher->id)
                ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
                ->unless($accountId, fn ($query) => $query->whereNull('account_id')->where('session_id', $sessionId))
                ->first();

            if ($usage && $usage->usage_count >= $voucher->usage_limit_per_user) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Bạn đã sử dụng voucher này tối đa số lần cho phép.',
                ]);
            }
        }
    }

    /**
     * Calculate discount based on voucher type.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    protected function calculateDiscounts(Voucher $voucher, float $subtotal, float $shippingFee): array
    {
        $productDiscount = 0.0;
        $shippingDiscount = 0.0;
        $shippingAfterDiscount = $shippingFee;

        switch ($voucher->type) {
            case 'percent':
                $productDiscount = $subtotal * ($voucher->value / 100);
                if ($voucher->max_discount) {
                    $productDiscount = min($productDiscount, (float) $voucher->max_discount);
                }
                break;

            case 'fixed':
                $productDiscount = min((float) $voucher->value, $subtotal);
                break;

            case 'free_shipping':
                $shippingDiscount = $voucher->value > 0
                    ? min((float) $voucher->value, $shippingFee)
                    : $shippingFee;

                if ($voucher->max_discount) {
                    $shippingDiscount = min($shippingDiscount, (float) $voucher->max_discount);
                }

                $shippingAfterDiscount = max($shippingFee - $shippingDiscount, 0);
                break;
        }

        $productDiscount = min($productDiscount, $subtotal);
        $shippingDiscount = min($shippingDiscount, $shippingFee);

        return [
            round($productDiscount, 2),
            round($shippingDiscount, 2),
            round($shippingAfterDiscount, 2),
        ];
    }

    /**
     * Persist voucher usage once order has been placed.
     */
    public function recordUsage(Voucher $voucher, int $orderId, ?int $accountId, string $sessionId, float $discountAmount, ?string $ip = null): void
    {
        VoucherHistory::recordUsage(
            voucherId: $voucher->id,
            orderId: $orderId,
            accountId: $accountId,
            discountAmount: $discountAmount,
            ip: $ip,
            sessionId: $sessionId
        );

        VoucherUserUsage::incrementFor($voucher->id, $accountId, $sessionId);
    }

    /**
     * Forget cache for a voucher (if caching is used)
     */
    public function forgetCache(string $code): void
    {
        $cacheKey = "voucher:{$code}";
        Cache::forget($cacheKey);
        Log::debug('Voucher cache cleared', ['code' => $code]);
    }

    /**
     * Log voucher history/activity (for admin actions)
     */
    public function logHistory(Voucher $voucher, string $action, ?array $before, ?array $after, string $note): void
    {
        Log::info('Voucher action logged', [
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'note' => $note,
        ]);
    }

    /**
     * Validate and apply voucher (for admin testing)
     */
    public function validateAndApplyVoucher(string $code, array $orderData, ?int $accountId = null, array $options = []): array
    {
        $subtotal = (float) ($orderData['subtotal'] ?? 0);
        $shippingFee = (float) ($orderData['shipping_fee'] ?? 0);
        $sessionId = $orderData['session_id'] ?? '';

        try {
            $result = $this->validate($code, $subtotal, $shippingFee, $accountId, $sessionId);

            return [
                'success' => true,
                'message' => 'Voucher hợp lệ và có thể áp dụng.',
                'data' => $result,
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ];
        } catch (\Throwable $e) {
            Log::error('Voucher validation failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể kiểm tra voucher. Vui lòng thử lại.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkVoucherEligibility(string $voucherCode, array $orderData, int $userId = null): array
    {
        return $this->validateAndApplyVoucher($voucherCode, $orderData, $userId);
    }
}
