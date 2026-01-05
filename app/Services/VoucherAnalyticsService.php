<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherAnalyticsService
{
    /**
     * Lấy tổng quan thống kê
     */
    public function getOverallStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Chỉ tính các đơn hàng đã thanh toán thành công và chưa bị hủy
        $query = Order::whereNotNull('voucher_id')
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->sum('final_price');
        $totalDiscount = $query->sum('voucher_discount');
        $uniqueUsers = $query->distinct('account_id')->count('account_id');
        $averageOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;
        $activeVouchers = Voucher::where('is_active', true)
            ->where(function ($q) use ($startDate, $endDate) {
                if ($startDate) {
                    $q->where('start_time', '<=', $endDate ?? Carbon::now());
                }
                if ($endDate) {
                    $q->where('end_time', '>=', $startDate ?? Carbon::now()->subYear());
                }
            })
            ->count();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'unique_customers' => $uniqueUsers,
            'average_order_value' => $averageOrderValue,
            'active_vouchers' => $activeVouchers,
        ];
    }

    /**
     * Lấy top vouchers theo tiêu chí
     */
    public function getTopVouchers(string $sortBy = 'revenue', int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Chỉ tính các đơn hàng đã thanh toán thành công và chưa bị hủy
        $query = Voucher::withCount(['orders' => function ($q) use ($startDate, $endDate) {
            $q->where('payment_status', 'paid')
                ->where('status', '!=', 'cancelled');
            if ($startDate) {
                $q->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->where('created_at', '<=', $endDate);
            }
        }])
            ->withSum(['orders' => function ($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'paid')
                    ->where('status', '!=', 'cancelled');
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('created_at', '<=', $endDate);
                }
            }], 'final_price')
            ->withSum(['orders' => function ($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'paid')
                    ->where('status', '!=', 'cancelled');
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('created_at', '<=', $endDate);
                }
            }], 'voucher_discount');

        // Laravel tự động tạo tên column: orders_sum_final_price và orders_sum_voucher_discount
        $vouchers = match ($sortBy) {
            'revenue' => $query->orderByDesc('orders_sum_final_price')->limit($limit)->get(),
            'usage' => $query->orderByDesc('orders_count')->limit($limit)->get(),
            'discount' => $query->orderByDesc('orders_sum_voucher_discount')->limit($limit)->get(),
            default => $query->orderByDesc('orders_count')->limit($limit)->get(),
        };

        // Map về format mà view mong đợi
        return $vouchers->map(function ($voucher) {
            $totalRevenue = (float) ($voucher->orders_sum_final_price ?? 0);
            $totalDiscount = (float) ($voucher->orders_sum_voucher_discount ?? 0);
            $totalOrders = (int) ($voucher->orders_count ?? 0);
            $netRevenue = $totalRevenue - $totalDiscount;
            $roi = $totalDiscount > 0 ? (($netRevenue - $totalDiscount) / $totalDiscount) * 100 : 0;

            return [
                'voucher' => $voucher,
                'total_revenue' => $totalRevenue,
                'total_discount' => $totalDiscount,
                'total_orders' => $totalOrders,
                'roi' => round($roi, 2),
            ];
        })->toArray();
    }

    /**
     * Lấy conversion rates theo từng voucher
     */
    public function getConversionRates(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Chỉ tính các đơn hàng đã thanh toán thành công và chưa bị hủy
        $query = Order::whereNotNull('voucher_id')
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->select('voucher_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('voucher_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $results = $query->get();

        return $results->map(function ($item) {
            $voucher = Voucher::find($item->voucher_id);
            if (! $voucher) {
                return null;
            }

            $usageCount = $voucher->histories()->count();
            $orderCount = (int) $item->order_count;
            $conversionRate = $usageCount > 0 ? ($orderCount / $usageCount) * 100 : 0;

            return [
                'voucher_code' => $voucher->code,
                'voucher_name' => $voucher->name ?? '—',
                'usage_count' => $usageCount,
                'order_count' => $orderCount,
                'conversion_rate' => round($conversionRate, 2),
            ];
        })->filter()->sortByDesc('conversion_rate')->values()->toArray();
    }

    /**
     * Lấy ROI tracking theo từng voucher
     */
    public function getROITracking(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Chỉ tính các đơn hàng đã thanh toán thành công và chưa bị hủy
        $query = Order::whereNotNull('voucher_id')
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->select('voucher_id', DB::raw('SUM(final_price) as total_revenue'), DB::raw('SUM(voucher_discount) as total_discount'))
            ->groupBy('voucher_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $results = $query->get();

        return $results->map(function ($item) {
            $voucher = Voucher::find($item->voucher_id);
            if (! $voucher) {
                return null;
            }

            $totalRevenue = (float) $item->total_revenue;
            $totalDiscount = (float) $item->total_discount;
            $netProfit = $totalRevenue - $totalDiscount;
            $roi = $totalDiscount > 0 ? (($netProfit - $totalDiscount) / $totalDiscount) * 100 : 0;

            return [
                'voucher_code' => $voucher->code,
                'voucher_name' => $voucher->name ?? '—',
                'total_revenue' => $totalRevenue,
                'total_discount' => $totalDiscount,
                'net_profit' => $netProfit,
                'roi' => round($roi, 2),
            ];
        })->filter()->sortByDesc('roi')->values()->toArray();
    }

    /**
     * Lấy performance của một voucher cụ thể
     */
    public function getVoucherPerformance(int $voucherId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $voucher = Voucher::findOrFail($voucherId);

        // Chỉ tính các đơn hàng đã thanh toán thành công và chưa bị hủy
        $query = Order::where('voucher_id', $voucherId)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalOrders = $query->count();
        $totalRevenue = (clone $query)->sum('final_price');
        $totalDiscount = (clone $query)->sum('voucher_discount');
        $uniqueUsers = (clone $query)->distinct('account_id')->count('account_id');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Tính conversion rate: số đơn hàng / số lượt sử dụng voucher
        $usageCount = $voucher->histories()->count();
        $conversionRate = $usageCount > 0 ? ($totalOrders / $usageCount) * 100 : 0;

        // Tính ROI
        $netRevenue = $totalRevenue - $totalDiscount;
        $roi = $totalDiscount > 0 ? (($netRevenue - $totalDiscount) / $totalDiscount) * 100 : 0;

        // Revenue impact = total revenue
        $revenueImpact = $totalRevenue;

        // Tính new customers (khách hàng mới trong khoảng thời gian này)
        $newCustomersQuery = (clone $query)->whereHas('account', function ($q) use ($startDate) {
            if ($startDate) {
                $q->where('created_at', '>=', $startDate);
            }
        });
        $newCustomers = $newCustomersQuery->distinct('account_id')->count('account_id');

        // Customer Acquisition Cost = total_discount / new_customers
        $customerAcquisitionCost = $newCustomers > 0 ? ($totalDiscount / $newCustomers) : 0;

        return [
            'voucher' => $voucher,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'unique_users' => $uniqueUsers,
            'unique_customers' => $uniqueUsers, // Alias cho view
            'avg_order_value' => $avgOrderValue,
            'conversion_rate' => round($conversionRate, 2),
            'roi' => round($roi, 2),
            'revenue_impact' => $revenueImpact,
            'new_customers' => $newCustomers,
            'customer_acquisition_cost' => round($customerAcquisitionCost, 2),
        ];
    }

    /**
     * Lấy revenue trend theo period
     */
    public function getRevenueTrend(int $voucherId, string $period = 'daily', ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Chỉ tính các đơn hàng đã thanh toán thành công và chưa bị hủy
        $query = Order::where('voucher_id', $voucherId)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $results = $query->select(
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('SUM(final_price) as revenue'),
            DB::raw('SUM(voucher_discount) as discount'),
            DB::raw('COUNT(*) as orders')
        )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $results->map(function ($item) {
            return [
                'period' => $item->period,
                'revenue' => (float) $item->revenue,
                'discount' => (float) $item->discount,
                'orders' => (int) $item->orders,
            ];
        })->toArray();
    }
}
