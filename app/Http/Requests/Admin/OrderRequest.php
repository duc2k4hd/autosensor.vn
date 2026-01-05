<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null || $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'session_id' => ['nullable', 'string', 'max:191'],
            'receiver_name' => ['required', 'string', 'max:191'],
            'receiver_phone' => ['required', 'string', 'max:50'],
            'receiver_email' => ['nullable', 'email', 'max:191'],
            'shipping_address' => ['required', 'string', 'max:191'],
            'shipping_province_id' => ['required', 'integer'],
            'shipping_district_id' => ['required', 'integer'],
            'shipping_ward_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:cod,bank_transfer,qr,momo,zalopay,payos'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
            'transaction_code' => ['nullable', 'string', 'max:191'],
            'shipping_partner' => ['nullable', 'in:viettelpost,ghtk,ghn'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'voucher_code' => ['nullable', 'string', 'max:191'],
            'voucher_discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,processing,completed,cancelled'],
            'delivery_status' => ['nullable', 'in:pending,shipped,delivered,returned,cancelled'],
            'customer_note' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items.*', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items.*', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items.*', 'numeric', 'min:0'],
        ];
    }
}
