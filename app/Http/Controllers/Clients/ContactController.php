<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function show(): View
    {
        $productNew = Product::active()
            ->orderByDesc('created_at')
            ->inRandomOrder()
            ->limit(9)
            ->get() ?? collect();

        Product::preloadImages($productNew);

        return view('clients.pages.home.contact', compact('productNew'));
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $attachmentPath = null;

        try {
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('contact-attachments', 'public');
            }

            $contact = Contact::create([
                'account_id' => auth('web')->id(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'attachment_path' => $attachmentPath,
                'ip' => $request->ip(),
                'is_read' => false,
                'status' => Contact::STATUS_NEW,
                'source' => 'contact_form',
            ]);

            // Gửi thông báo cho admin về contact mới
            $this->notificationService->notifyNewContact(
                $contact->id,
                $contact->name,
                $contact->subject
            );

            return redirect()
                ->route('client.contact.index')
                ->with('success', 'AutoSensor Việt Nam đã nhận thông tin. Chúng tôi sẽ liên hệ trong 24 giờ.');
        } catch (\Throwable $e) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Đã có lỗi xảy ra, vui lòng thử lại sau.');
        }
    }
}
