<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderFromCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null || $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:191'],
            'receiver_phone' => ['required', 'string', 'max:50'],
            'receiver_email' => ['nullable', 'email', 'max:191'],
            'shipping_address' => ['required', 'string', 'max:191'],
            'shipping_province_id' => ['required', 'integer'],
            'shipping_district_id' => ['required', 'integer'],
            'shipping_ward_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:cod,bank_transfer,qr,momo,zalopay,payos'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
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
        ];
    }
}
