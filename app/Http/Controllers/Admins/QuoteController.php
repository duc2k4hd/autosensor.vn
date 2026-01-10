<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::with('account');

        // Filters
        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhereHas('account', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $quotes = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        // Stats
        $stats = [
            'total' => Quote::count(),
            'new' => Quote::where('status', 'new')->count(),
            'contacted' => Quote::where('status', 'contacted')->count(),
            'done' => Quote::where('status', 'done')->count(),
            'cancelled' => Quote::where('status', 'cancelled')->count(),
        ];

        return view('admins.quotes.index', compact('quotes', 'stats'));
    }

    public function show(Quote $quote)
    {
        $quote->load('account');

        return view('admins.quotes.show', compact('quote'));
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $request->validate([
            'status' => ['required', 'in:new,contacted,done,cancelled'],
        ]);

        $quote->update([
            'status' => $request->status,
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('success', 'Đã cập nhật trạng thái báo giá.');
    }

    public function downloadPdf(Quote $quote)
    {
        if ($quote->pdf_path && Storage::disk('local')->exists($quote->pdf_path)) {
            $filePath = Storage::disk('local')->path($quote->pdf_path);
            return response()->download($filePath, 'bao_gia_'.$quote->id.'.pdf');
        }

        // Generate PDF on the fly if not exists
        $settings = View::shared('settings');
        $pdf = Pdf::loadView('clients.pages.quote.pdf', [
            'quote' => $quote,
            'items' => collect($quote->cart_snapshot ?? []),
            'settings' => $settings,
        ])->setPaper('a4', 'portrait')
          ->setOption('defaultFont', 'DejaVu Sans')
          ->setOption('isRemoteEnabled', true)
          ->setOption('isHtml5ParserEnabled', true);

        return $pdf->download('bao_gia_'.$quote->id.'.pdf');
    }
}
