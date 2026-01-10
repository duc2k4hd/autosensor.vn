<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\QuickConsultationLead;
use Illuminate\Http\Request;

class QuickConsultationLeadController extends Controller
{
    /**
     * Danh sách leads tư vấn nhanh
     */
    public function index(Request $request)
    {
        $query = QuickConsultationLead::with('product');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by trigger type
        if ($request->filled('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        // Filter by contacted status
        if ($request->filled('is_contacted')) {
            $query->where('is_contacted', $request->is_contacted === '1');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'contacted' => $query->orderBy('is_contacted', 'desc')->latest('created_at'),
            default => $query->latest('created_at'),
        };

        $perPage = $request->get('per_page', 20);
        $leads = $query->paginate($perPage)->withQueryString();

        // Statistics
        $stats = [
            'total' => QuickConsultationLead::count(),
            'new' => QuickConsultationLead::where('is_contacted', false)->count(),
            'contacted' => QuickConsultationLead::where('is_contacted', true)->count(),
            'view_time' => QuickConsultationLead::where('trigger_type', 'view_time')->count(),
            'multiple_products' => QuickConsultationLead::where('trigger_type', 'multiple_products')->count(),
        ];

        return view('admins.quick-consultation-leads.index', [
            'leads' => $leads,
            'stats' => $stats,
            'filters' => $request->only(['search', 'trigger_type', 'is_contacted', 'date_from', 'date_to', 'sort', 'per_page']),
        ]);
    }

    /**
     * Chi tiết lead
     */
    public function show($id)
    {
        $lead = QuickConsultationLead::with('product')->findOrFail($id);

        return view('admins.quick-consultation-leads.show', compact('lead'));
    }

    /**
     * Đánh dấu đã liên hệ
     */
    public function markContacted(Request $request, $id)
    {
        $lead = QuickConsultationLead::findOrFail($id);
        $lead->update([
            'is_contacted' => true,
            'contacted_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã đánh dấu đã liên hệ.');
    }

    /**
     * Xóa lead
     */
    public function destroy($id)
    {
        $lead = QuickConsultationLead::findOrFail($id);
        $lead->delete();

        return redirect()->route('admin.quick-consultation-leads.index')
            ->with('success', 'Đã xóa lead thành công.');
    }
}
