<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Stats cơ bản
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_customers' => Account::where('role', Account::ROLE_USER)->count(),
            'total_orders' => Order::count(),
            'total_categories' => Category::where('is_active', true)->count(),
        ];

        // Thống kê đơn hàng
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $ordersToday = Order::whereDate('created_at', $today)->count();
        $ordersYesterday = Order::whereDate('created_at', $yesterday)->count();
        $todayChange = $ordersYesterday > 0
            ? round((($ordersToday - $ordersYesterday) / $ordersYesterday) * 100, 1)
            : ($ordersToday > 0 ? 100 : 0);

        $orders = [
            'today' => $ordersToday,
            'today_change' => $todayChange,
            'this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        // Thống kê doanh thu
        $revenueToday = (float) (Order::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('final_price') ?? 0);
        $revenueYesterday = (float) (Order::whereDate('created_at', $yesterday)
            ->where('status', '!=', 'cancelled')
            ->sum('final_price') ?? 0);
        $revenueTodayChange = $revenueYesterday > 0
            ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100, 1)
            : ($revenueToday > 0 ? 100 : 0);

        $revenueThisMonth = (float) (Order::where('created_at', '>=', $thisMonth)
            ->where('status', '!=', 'cancelled')
            ->sum('final_price') ?? 0);
        $revenueLastMonth = (float) (Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('final_price') ?? 0);
        $revenueMonthChange = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        $revenue = [
            'today' => $revenueToday,
            'today_change' => $revenueTodayChange,
            'this_month' => $revenueThisMonth,
            'month_change' => $revenueMonthChange,
            'this_year' => (float) (Order::whereYear('created_at', Carbon::now()->year)
                ->where('status', '!=', 'cancelled')
                ->sum('final_price') ?? 0),
            'all_time' => (float) (Order::where('status', '!=', 'cancelled')->sum('final_price') ?? 0),
        ];

        // Thống kê thanh toán
        $paidOrders = Order::whereHas('payments', function ($q) {
            $q->where('status', 'paid');
        })->count();
        $unpaidOrders = Order::whereDoesntHave('payments', function ($q) {
            $q->where('status', 'paid');
        })->count();
        $totalOrdersForPayment = $paidOrders + $unpaidOrders;
        $paidPercentage = $totalOrdersForPayment > 0
            ? round(($paidOrders / $totalOrdersForPayment) * 100, 1)
            : 0;

        $paymentStats = [
            'paid' => $paidOrders,
            'unpaid' => $unpaidOrders,
            'paid_percentage' => $paidPercentage,
        ];

        // Thống kê giao hàng (giả sử status 'completed' là đã giao)
        $deliveredOrders = Order::where('status', 'completed')->count();
        $shippingOrders = Order::where('status', 'processing')->count();
        $pendingDeliveryOrders = Order::where('status', 'pending')->count();
        $totalOrdersForDelivery = $deliveredOrders + $shippingOrders + $pendingDeliveryOrders;
        $deliveredPercentage = $totalOrdersForDelivery > 0
            ? round(($deliveredOrders / $totalOrdersForDelivery) * 100, 1)
            : 0;

        $deliveryStats = [
            'delivered' => $deliveredOrders,
            'shipping' => $shippingOrders,
            'pending' => $pendingDeliveryOrders,
            'delivered_percentage' => $deliveredPercentage,
        ];

        // Top 10 sản phẩm bán chạy
        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'))
            ->with('product:id,sku,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product->name ?? 'N/A',
                    'sku' => $item->product->sku ?? 'N/A',
                    'total_sold' => (int) ($item->total_sold ?? 0),
                    'total_revenue' => (float) ($item->total_revenue ?? 0),
                ];
            });

        // Đơn hàng gần đây
        $recentOrders = Order::with(['account:id,name', 'shippingAddress:id,full_name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                // Model Order đã có accessor final_price tự động tính
                $order->payment_status = $order->payments()->where('status', 'paid')->exists() ? 'paid' : 'unpaid';
                $order->receiver_name = $order->shippingAddress->full_name ?? null;

                return $order;
            });

        // Top 10 danh mục
        $topCategories = Category::withCount(['products' => function ($query) {
            $query->where('is_active', true);
        }])
            ->where('is_active', true)
            ->orderByDesc('products_count')
            ->limit(10)
            ->get()
            ->map(function ($category) {
                $category->product_count = $category->products_count;

                return $category;
            });

        // Thống kê voucher
        $voucherStats = [
            'total' => Voucher::count(),
            'active' => Voucher::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('start_time')->orWhere('start_time', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_time')->orWhere('end_time', '>=', now());
                })
                ->count(),
            'used' => DB::table('voucher_histories')->count(),
        ];

        // Thống kê liên hệ
        $newContacts = Contact::whereDate('created_at', $today)->count();
        $unreadContacts = Contact::where('is_read', false)->count();

        // Sản phẩm sắp hết hàng / hết hàng (bao gồm cả variant)
        $lowStockProducts = Product::where('is_active', true)
            ->whereNotNull('stock_quantity')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get(['id', 'sku', 'name', 'stock_quantity']);

        // Lấy thêm variants có stock thấp
        $lowStockVariants = \App\Models\ProductVariant::where('is_active', true)
            ->whereNotNull('stock_quantity')
            ->where('stock_quantity', '<=', 10)
            ->with('product:id,sku,name')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        // Thống kê 7 ngày gần nhất
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('d/m');
            $dailyRevenue = (float) (Order::whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('final_price') ?? 0);
            $orderCount = Order::whereDate('created_at', $date)->count();

            $dailyStats[] = [
                'date' => $dateStr,
                'revenue' => $dailyRevenue,
                'orders' => $orderCount,
            ];
        }

        // Thống kê 12 tháng gần nhất
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $monthStr = $month->format('m/Y');
            $monthlyRevenue = (float) (Order::whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->sum('final_price') ?? 0);

            $monthlyStats[] = [
                'month' => $monthStr,
                'revenue' => $monthlyRevenue,
            ];
        }

        return view('admins.dashboard.index', compact(
            'stats',
            'orders',
            'revenue',
            'paymentStats',
            'deliveryStats',
            'topProducts',
            'recentOrders',
            'topCategories',
            'voucherStats',
            'newContacts',
            'unreadContacts',
            'dailyStats',
            'monthlyStats',
            'lowStockProducts',
            'lowStockVariants'
        ));
    }
}
