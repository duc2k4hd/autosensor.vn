<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Các route admin đã được bảo vệ bởi middleware 'auth' và 'admin'
        // nên không cần kiểm tra guard riêng tại đây.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
            'delivery_status' => ['nullable', 'in:pending,shipped,delivered,returned,cancelled'],
        ];
    }
}
