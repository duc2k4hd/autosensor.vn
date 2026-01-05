<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ContactFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:new,processing,done,spam'],
            'source' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'in:latest,oldest,status'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
