<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
            'detail_address' => ['required', 'string', 'max:500'],
            'ward' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address_type' => ['nullable', 'in:home,work'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
