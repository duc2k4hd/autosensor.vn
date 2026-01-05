<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voucher_code' => ['required', 'string', 'max:50'],
            'order_data' => ['required', 'array'],
            'order_data.subtotal' => ['required', 'numeric', 'min:0'],
            'order_data.shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'order_data.shipping_fee_after_discount' => ['nullable', 'numeric', 'min:0'],
            'order_data.items' => ['nullable', 'array'],
        ];
    }
}
