<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $voucherId = $this->route('voucher')->id ?? null;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucherId)],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'type' => ['required', Rule::in([Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED, Voucher::TYPE_FREE_SHIPPING])],
            'value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'start_at' => ['nullable', 'date'], // Alias for start_time
            'end_at' => ['nullable', 'date', 'after:start_at'], // Alias for end_time
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in([Voucher::STATUS_ACTIVE, Voucher::STATUS_SCHEDULED, Voucher::STATUS_DISABLED])], // Alias for is_active
            'apply_for' => ['nullable', 'array'],
            'applicable_to' => ['nullable', Rule::in([Voucher::APPLICABLE_ALL, Voucher::APPLICABLE_CATEGORIES, Voucher::APPLICABLE_PRODUCTS])],
            'applicable_ids' => ['nullable', 'array'],
            'applicable_ids.*' => ['integer'],
        ];
    }
}
