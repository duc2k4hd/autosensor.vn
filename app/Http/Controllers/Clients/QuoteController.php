<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QuoteController extends Controller
{
    public function store(Request $request)
    {
        // Lấy giỏ hiện tại (account hoặc session), luôn kèm items + product + variant
        $cart = $this->getCurrentCart($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('client.cart.index')
                ->with('warning', 'Giỏ hàng đang trống, không thể yêu cầu báo giá.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Vui lòng nhập tên của bạn để chúng tôi tiện liên hệ.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $account = auth('web')->user();

        $items = $cart->items->map(function ($item) {
            $product = $item->product;
            if (! $product) {
                return null;
            }

            $variant = $item->variant;

            $unitPrice = $variant && $variant->is_active
                ? (float) $variant->display_price
                : (float) ($item->price ?? $product->resolveCartPrice());

            $quantity = max((int) ($item->quantity ?? 1), 1);
            $lineTotal = $unitPrice * $quantity;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $variant?->sku ?? $product->sku,
                'variant_name' => $variant?->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        })->filter()->values();

        $totalAmount = (float) $items->sum('line_total');

        $quote = Quote::create([
            'account_id' => $account?->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'note' => $data['note'] ?? null,
            'total_amount' => $totalAmount,
            'cart_snapshot' => $items->all(),
            'status' => 'new',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Sinh PDF đơn giản lưu lại để CSKH dùng khi cần
        try {
            $pdf = Pdf::loadView('clients.pages.quote.pdf', [
                'quote' => $quote,
                'items' => $items,
                'settings' => $request->attributes->get('settings') ?? null,
            ])->setPaper('a4', 'portrait')
              ->setOption('defaultFont', 'DejaVu Sans')
              ->setOption('isRemoteEnabled', true)
              ->setOption('isHtml5ParserEnabled', true);

            $filename = 'quote_'.$quote->id.'.pdf';
            $path = 'quotes/'.$filename;

            Storage::disk('local')->put($path, $pdf->output());

            $quote->update(['pdf_path' => $path]);
        } catch (\Throwable $e) {
            Log::warning('Quick quote: failed to generate PDF', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('client.cart.index')
            ->with('success', 'Đã gửi yêu cầu báo giá. Đội ngũ AutoSensor sẽ liên hệ với bạn trong thời gian sớm nhất.');
    }

    protected function getCurrentCart(Request $request): ?Cart
    {
        $accountId = auth('web')->id();
        $sessionId = $request->session()->getId();

        $query = Cart::query()->with([
            'items.product.currentFlashSaleItem.flashSale',
            'items.variant',
        ]);

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id')->where('session_id', $sessionId);
        }

        return $query->latest('id')->first();
    }
}

