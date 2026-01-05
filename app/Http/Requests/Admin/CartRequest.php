<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null || $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:active,ordered,abandoned'],
        ];
    }
}
