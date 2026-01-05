<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    /**
     * Trang chủ báo cáo
     */
    public function index()
    {
        return view('admins.reports.index');
    }

    /**
     * Báo cáo doanh thu
     */
    public function revenue(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        $query = Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);

        $data = match ($groupBy) {
            'day' => $this->revenueByDay($query, $dateFrom, $dateTo),
            'week' => $this->revenueByWeek($query, $dateFrom, $dateTo),
            'month' => $this->revenueByMonth($query, $dateFrom, $dateTo),
            default => $this->revenueByDay($query, $dateFrom, $dateTo),
        };

        // Calculate summary with a fresh query
        $summaryQuery = Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);

        $totalRevenue = $summaryQuery->sum('final_price');
        $totalOrders = $summaryQuery->count();

        $summary = [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
        ];

        return view('admins.reports.revenue', compact('data', 'summary', 'dateFrom', 'dateTo', 'groupBy'));
    }

    /**
     * Báo cáo sản phẩm
     */
    public function products(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $products = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'))
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
            })
            ->with('product:id,sku,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->paginate(50);

        return view('admins.reports.products', compact('products', 'dateFrom', 'dateTo'));
    }

    /**
     * Báo cáo khách hàng
     */
    public function customers(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $customers = Account::select('accounts.*', DB::raw('COUNT(orders.id) as order_count'), DB::raw('SUM(orders.final_price) as total_spent'))
            ->leftJoin('orders', 'accounts.id', '=', 'orders.account_id')
            ->where('accounts.role', Account::ROLE_USER)
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereNull('orders.created_at')
                    ->orWhereBetween('orders.created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
                    ->where('orders.status', '!=', 'cancelled');
            })
            ->groupBy('accounts.id')
            ->orderByDesc('total_spent')
            ->paginate(50);

        return view('admins.reports.customers', compact('customers', 'dateFrom', 'dateTo'));
    }

    /**
     * Báo cáo tồn kho
     */
    public function inventory(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $movements = InventoryMovement::with(['product:id,sku,name', 'account:id,name'])
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->orderByDesc('created_at')
            ->paginate(50);

        $summary = [
            'total_imports' => InventoryMovement::where('type', 'import')
                ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
                ->sum('quantity_change'),
            'total_exports' => abs(InventoryMovement::whereIn('type', ['export', 'order'])
                ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
                ->sum('quantity_change')),
        ];

        return view('admins.reports.inventory', compact('movements', 'summary', 'dateFrom', 'dateTo'));
    }

    /**
     * Export báo cáo
     */
    public function export(Request $request)
    {
        $type = $request->get('type'); // revenue, products, customers, inventory
        $format = $request->get('format', 'excel'); // excel, pdf
        $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        return match ($type) {
            'revenue' => $this->exportRevenue($dateFrom, $dateTo, $format),
            'products' => $this->exportProducts($dateFrom, $dateTo, $format),
            'customers' => $this->exportCustomers($dateFrom, $dateTo, $format),
            'inventory' => $this->exportInventory($dateFrom, $dateTo, $format),
            default => back()->with('error', 'Loại báo cáo không hợp lệ.'),
        };
    }

    protected function revenueByDay($query, $dateFrom, $dateTo)
    {
        return $query->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(final_price) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    protected function revenueByWeek($query, $dateFrom, $dateTo)
    {
        return $query->select(DB::raw('YEARWEEK(created_at) as week'), DB::raw('SUM(final_price) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('week')
            ->orderBy('week')
            ->get();
    }

    protected function revenueByMonth($query, $dateFrom, $dateTo)
    {
        return $query->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('SUM(final_price) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    protected function exportRevenue($dateFrom, $dateTo, $format)
    {
        $data = Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(final_price) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admins.reports.exports.revenue-pdf', compact('data', 'dateFrom', 'dateTo'));

            return $pdf->download('revenue_report_'.$dateFrom.'_'.$dateTo.'.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Doanh thu');

        $sheet->fromArray(['Ngày', 'Doanh thu', 'Số đơn'], null, 'A1');
        $row = 2;
        foreach ($data as $item) {
            $sheet->fromArray([
                $item->date,
                number_format($item->revenue, 0, ',', '.'),
                $item->orders,
            ], null, 'A'.$row);
            $row++;
        }

        return $this->downloadExcel($spreadsheet, 'revenue_report_'.$dateFrom.'_'.$dateTo.'.xlsx');
    }

    protected function exportProducts($dateFrom, $dateTo, $format)
    {
        $data = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'))
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
            })
            ->with('product:id,sku,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admins.reports.exports.products-pdf', compact('data', 'dateFrom', 'dateTo'));

            return $pdf->download('products_report_'.$dateFrom.'_'.$dateTo.'.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sản phẩm');

        $sheet->fromArray(['SKU', 'Tên sản phẩm', 'Số lượng bán', 'Doanh thu'], null, 'A1');
        $row = 2;
        foreach ($data as $item) {
            $sheet->fromArray([
                $item->product->sku ?? '',
                $item->product->name ?? '',
                $item->total_sold,
                number_format($item->total_revenue, 0, ',', '.'),
            ], null, 'A'.$row);
            $row++;
        }

        return $this->downloadExcel($spreadsheet, 'products_report_'.$dateFrom.'_'.$dateTo.'.xlsx');
    }

    protected function exportCustomers($dateFrom, $dateTo, $format)
    {
        $data = Account::select('accounts.*', DB::raw('COUNT(orders.id) as order_count'), DB::raw('SUM(orders.final_price) as total_spent'))
            ->leftJoin('orders', 'accounts.id', '=', 'orders.account_id')
            ->where('accounts.role', Account::ROLE_USER)
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereNull('orders.created_at')
                    ->orWhereBetween('orders.created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
                    ->where('orders.status', '!=', 'cancelled');
            })
            ->groupBy('accounts.id')
            ->orderByDesc('total_spent')
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admins.reports.exports.customers-pdf', compact('data', 'dateFrom', 'dateTo'));

            return $pdf->download('customers_report_'.$dateFrom.'_'.$dateTo.'.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Khách hàng');

        $sheet->fromArray(['Tên', 'Email', 'Số đơn', 'Tổng chi tiêu'], null, 'A1');
        $row = 2;
        foreach ($data as $item) {
            $sheet->fromArray([
                $item->name,
                $item->email,
                $item->order_count ?? 0,
                number_format($item->total_spent ?? 0, 0, ',', '.'),
            ], null, 'A'.$row);
            $row++;
        }

        return $this->downloadExcel($spreadsheet, 'customers_report_'.$dateFrom.'_'.$dateTo.'.xlsx');
    }

    protected function exportInventory($dateFrom, $dateTo, $format)
    {
        $data = InventoryMovement::with(['product:id,sku,name', 'account:id,name'])
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admins.reports.exports.inventory-pdf', compact('data', 'dateFrom', 'dateTo'));

            return $pdf->download('inventory_report_'.$dateFrom.'_'.$dateTo.'.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tồn kho');

        $sheet->fromArray(['Ngày', 'Sản phẩm', 'Loại', 'Thay đổi', 'Tồn trước', 'Tồn sau', 'Người thao tác'], null, 'A1');
        $row = 2;
        foreach ($data as $item) {
            $sheet->fromArray([
                $item->created_at->format('Y-m-d H:i:s'),
                $item->product->name ?? '',
                $item->type,
                $item->quantity_change,
                $item->stock_before,
                $item->stock_after,
                $item->account->name ?? 'System',
            ], null, 'A'.$row);
            $row++;
        }

        return $this->downloadExcel($spreadsheet, 'inventory_report_'.$dateFrom.'_'.$dateTo.'.xlsx');
    }

    protected function downloadExcel(Spreadsheet $spreadsheet, string $fileName)
    {
        $tempDir = storage_path('app/tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $fullPath = $tempDir.'/'.$fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}
