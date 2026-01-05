<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactFilterRequest;
use App\Http\Requests\Admin\ContactReplyRequest;
use App\Http\Requests\Admin\ContactStatusUpdateRequest;
use App\Models\Contact;
use App\Services\ContactReplyService;
use App\Services\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ContactController extends Controller
{
    protected ContactService $contactService;

    protected ContactReplyService $replyService;

    public function __construct(ContactService $contactService, ContactReplyService $replyService)
    {
        $this->contactService = $contactService;
        $this->replyService = $replyService;
    }

    /**
     * Danh sách liên hệ với filters
     */
    public function index(ContactFilterRequest $request)
    {
        $query = Contact::with('account');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        $query->byStatus($request->status);

        // Filter by source
        $query->bySource($request->source);

        // Filter by user_id (account_id)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        $query->dateRange($request->date_from, $request->date_to);

        // Sort
        match ($request->sort) {
            'oldest' => $query->oldest('created_at'),
            'status' => $query->orderBy('status')->latest('created_at'),
            default => $query->latest('created_at'),
        };

        $perPage = $request->get('per_page', 20);
        $contacts = $query->paginate($perPage)->withQueryString();

        // Statistics
        $stats = [
            'total' => Contact::count(),
            'new' => Contact::new()->count(),
            'processing' => Contact::processing()->count(),
            'done' => Contact::done()->count(),
            'spam' => Contact::spam()->count(),
        ];

        return view('admins.contacts.index', [
            'contacts' => $contacts,
            'stats' => $stats,
            'filters' => $request->validated(),
        ]);
    }

    /**
     * Chi tiết liên hệ
     */
    public function show(Contact $contact)
    {
        $contact->load(['account', 'replies.account']);

        return view('admins.contacts.show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Cập nhật trạng thái
     */
    public function updateStatus(ContactStatusUpdateRequest $request, Contact $contact)
    {
        try {
            $this->contactService->updateStatus(
                $contact,
                $request->status,
                $request->note
            );

            return back()->with('success', 'Đã cập nhật trạng thái liên hệ.');
        } catch (\Exception $e) {
            Log::error('Failed to update contact status', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thể cập nhật trạng thái: '.$e->getMessage());
        }
    }

    /**
     * Cập nhật ghi chú nội bộ
     */
    public function updateNote(Request $request, Contact $contact)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->contactService->updateNote($contact, $request->admin_note ?? '');

            return back()->with('success', 'Đã cập nhật ghi chú nội bộ.');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể cập nhật ghi chú: '.$e->getMessage());
        }
    }

    /**
     * Gửi email trả lời
     */
    public function reply(ContactReplyRequest $request, Contact $contact)
    {
        try {
            $attachment = $request->hasFile('attachment') ? $request->file('attachment') : null;

            $result = $this->replyService->sendReply(
                $contact,
                $request->message,
                $attachment
            );

            if ($result['success']) {
                return back()->with('success', $result['message']);
            } else {
                return back()->with('error', $result['error'] ?? 'Không thể gửi email trả lời.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to reply contact', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thể gửi email trả lời: '.$e->getMessage());
        }
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', 'string', 'in:mark_spam,mark_processing,mark_done,delete'],
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        try {
            $contactIds = $request->contact_ids;

            match ($request->action) {
                'mark_spam' => $this->contactService->bulkUpdateStatus($contactIds, 'spam'),
                'mark_processing' => $this->contactService->bulkUpdateStatus($contactIds, 'processing'),
                'mark_done' => $this->contactService->bulkUpdateStatus($contactIds, 'done'),
                'delete' => $this->contactService->bulkDelete($contactIds),
            };

            $actionLabels = [
                'mark_spam' => 'đánh dấu spam',
                'mark_processing' => 'chuyển sang đang xử lý',
                'mark_done' => 'chuyển sang đã xử lý',
                'delete' => 'xóa',
            ];

            return back()->with('success', 'Đã '.($actionLabels[$request->action] ?? 'thực hiện').' '.count($contactIds).' liên hệ.');
        } catch (\Exception $e) {
            Log::error('Failed to perform bulk action', [
                'action' => $request->action,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thể thực hiện thao tác: '.$e->getMessage());
        }
    }

    /**
     * Xóa mềm
     */
    public function destroy(Contact $contact)
    {
        try {
            $contact->delete();

            return redirect()
                ->route('admin.contacts.index')
                ->with('success', 'Đã xóa liên hệ.');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa liên hệ: '.$e->getMessage());
        }
    }

    /**
     * Khôi phục
     */
    public function restore($id)
    {
        try {
            $contact = Contact::withTrashed()->findOrFail($id);
            $contact->restore();

            return back()->with('success', 'Đã khôi phục liên hệ.');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể khôi phục liên hệ: '.$e->getMessage());
        }
    }

    /**
     * Tải xuống tệp đính kèm
     */
    public function downloadAttachment(Contact $contact)
    {
        if (! $contact->attachment_path) {
            return back()->with('error', 'Liên hệ không có tệp đính kèm.');
        }

        try {
            return response()->download(
                storage_path('app/public/'.$contact->attachment_path)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to download contact attachment', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thể tải tệp đính kèm.');
        }
    }

    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:4096'],
        ]);

        try {
            $path = $request->file('file')->store('contact-editor', 'public');

            return response()->json([
                'location' => Storage::disk('public')->url($path),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to upload contact editor image', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Không thể tải ảnh lên.'], 422);
        }
    }

    /**
     * Export contacts to Excel
     */
    public function export(ContactFilterRequest $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Contact::with('account');

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $query->byStatus($request->status);
        $query->bySource($request->source);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $query->dateRange($request->date_from, $request->date_to);

        match ($request->sort) {
            'oldest' => $query->oldest('created_at'),
            'status' => $query->orderBy('status')->latest('created_at'),
            default => $query->latest('created_at'),
        };

        $contacts = $query->get();

        // Create Excel file
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contacts');

        // Headers
        $headers = [
            'ID', 'Tên', 'Email', 'Số điện thoại', 'Tiêu đề', 'Nội dung',
            'Trạng thái', 'Nguồn', 'Đã đọc', 'Số lần trả lời', 'Ngày trả lời cuối',
            'Khách hàng', 'IP', 'Ghi chú admin', 'Ngày tạo', 'Ngày cập nhật',
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ];
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

        // Data
        $row = 2;
        foreach ($contacts as $contact) {
            $sheet->fromArray([
                $contact->id,
                $contact->name,
                $contact->email,
                $contact->phone,
                $contact->subject,
                $contact->message,
                $contact->status,
                $contact->source ?? '',
                $contact->is_read ? 'Có' : 'Không',
                $contact->reply_count ?? 0,
                $contact->last_replied_at?->format('Y-m-d H:i:s'),
                $contact->account?->name ?? '',
                $contact->ip ?? '',
                $contact->admin_note ?? '',
                $contact->created_at?->format('Y-m-d H:i:s'),
                $contact->updated_at?->format('Y-m-d H:i:s'),
            ], null, 'A'.$row);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'contacts_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';
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
