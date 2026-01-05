<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    /**
     * Danh sách affiliates
     */
    public function index(Request $request)
    {
        $query = Affiliate::with('account');

        // Filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $affiliates = $query->orderByDesc('created_at')->paginate(20);

        // Statistics
        $stats = [
            'total' => Affiliate::count(),
            'active' => Affiliate::where('status', 'active')->count(),
            'total_clicks' => Affiliate::sum('clicks'),
            'total_conversions' => Affiliate::sum('conversions'),
            'total_commission' => Affiliate::sum('total_commission'),
        ];

        return view('admins.affiliates.index', [
            'affiliates' => $affiliates,
            'stats' => $stats,
        ]);
    }

    /**
     * Tạo affiliate cho user
     */
    public function store(Request $request)
    {
        $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $account = Account::findOrFail($request->account_id);

        // Check if already has affiliate
        if ($account->affiliates()->exists()) {
            return back()->with('error', 'Tài khoản này đã có affiliate code.');
        }

        $code = $this->generateUniqueCode();
        $referralUrl = route('client.home.index').'?ref='.$code;

        $affiliate = Affiliate::create([
            'account_id' => $account->id,
            'code' => $code,
            'commission_rate' => $request->commission_rate ?? 5.0,
            'referral_url' => $referralUrl,
            'status' => 'active',
        ]);

        return back()->with('success', 'Đã tạo affiliate code cho tài khoản: '.$account->name);
    }

    /**
     * Cập nhật affiliate
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $request->validate([
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $affiliate->update([
            'commission_rate' => $request->commission_rate ?? $affiliate->commission_rate,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Đã cập nhật affiliate.');
    }

    /**
     * Xóa affiliate
     */
    public function destroy(Affiliate $affiliate)
    {
        $affiliate->delete();

        return back()->with('success', 'Đã xóa affiliate.');
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
