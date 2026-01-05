<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    /**
     * Hiển thị dashboard affiliate
     */
    public function index()
    {
        $account = Auth::guard('account')->user();

        if (! $account) {
            return redirect()->route('client.auth.login');
        }

        $affiliate = $account->affiliates()->first();

        // Nếu chưa có affiliate, tự động tạo
        if (! $affiliate) {
            $affiliate = $this->createAffiliateForAccount($account);
        }

        // Thống kê
        $stats = $this->getAffiliateStats($affiliate);

        // Lịch sử hoa hồng (từ orders có affiliate_code)
        $commissions = $this->getCommissionHistory($affiliate);

        return view('clients.pages.affiliate.index', [
            'affiliate' => $affiliate,
            'stats' => $stats,
            'commissions' => $commissions,
        ]);
    }

    /**
     * Tạo affiliate cho account
     */
    public function createAffiliateForAccount($account): Affiliate
    {
        $code = $this->generateUniqueCode();
        $referralUrl = route('client.home.index').'?ref='.$code;

        return Affiliate::create([
            'account_id' => $account->id,
            'code' => $code,
            'commission_rate' => 5.0, // Mặc định 5%
            'referral_url' => $referralUrl,
            'status' => 'active',
        ]);
    }

    /**
     * Lấy thống kê affiliate
     */
    protected function getAffiliateStats(Affiliate $affiliate): array
    {
        // Đếm số đơn hàng từ affiliate code này
        $orders = Order::where('affiliate_code', $affiliate->code)
            ->where('status', '!=', 'cancelled')
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('final_price');
        $totalCommission = $orders->sum(function ($order) use ($affiliate) {
            return $order->final_price * ($affiliate->commission_rate / 100);
        });

        // Cập nhật conversions
        $affiliate->update([
            'conversions' => $totalOrders,
            'total_commission' => $totalCommission,
        ]);

        return [
            'clicks' => $affiliate->clicks,
            'conversions' => $totalOrders,
            'conversion_rate' => $affiliate->clicks > 0 ? round(($totalOrders / $affiliate->clicks) * 100, 2) : 0,
            'total_revenue' => $totalRevenue,
            'total_commission' => $totalCommission,
            'commission_rate' => $affiliate->commission_rate,
            'pending_commission' => $this->calculatePendingCommission($affiliate),
            'paid_commission' => $this->calculatePaidCommission($affiliate),
        ];
    }

    /**
     * Tính hoa hồng đang chờ thanh toán
     */
    protected function calculatePendingCommission(Affiliate $affiliate): float
    {
        $orders = Order::where('affiliate_code', $affiliate->code)
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->get();

        return $orders->sum(function ($order) use ($affiliate) {
            return $order->final_price * ($affiliate->commission_rate / 100);
        });
    }

    /**
     * Tính hoa hồng đã thanh toán
     */
    protected function calculatePaidCommission(Affiliate $affiliate): float
    {
        $orders = Order::where('affiliate_code', $affiliate->code)
            ->where('status', 'completed')
            ->get();

        return $orders->sum(function ($order) use ($affiliate) {
            return $order->final_price * ($affiliate->commission_rate / 100);
        });
    }

    /**
     * Lấy lịch sử hoa hồng
     */
    protected function getCommissionHistory(Affiliate $affiliate)
    {
        return Order::where('affiliate_code', $affiliate->code)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /**
     * Copy link giới thiệu
     */
    public function copyLink(Request $request)
    {
        $account = Auth::guard('account')->user();

        if (! $account) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập']);
        }

        $affiliate = $account->affiliates()->first();

        if (! $affiliate) {
            $affiliate = $this->createAffiliateForAccount($account);
        }

        $link = $affiliate->referral_url;

        return response()->json([
            'success' => true,
            'link' => $link,
            'message' => 'Đã copy link giới thiệu',
        ]);
    }

    /**
     * Generate unique affiliate code
     */
    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Affiliate::where('code', $code)->exists());

        return $code;
    }
}
