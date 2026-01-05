<?php

namespace App\Http\Requests\Admin\FlashSale;

use App\Models\FlashSale;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashSaleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->flashSale());
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $flashSale = $this->flashSale();

        return [
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'max_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price.numeric' => 'Giá Flash Sale phải là số.',
            'sale_price.min' => 'Giá Flash Sale phải lớn hơn 0.',
            'stock.integer' => 'Số lượng phải là số nguyên.',
            'stock.min' => 'Số lượng phải lớn hơn hoặc bằng 0.',
            'max_per_user.min' => 'Giới hạn mỗi người phải lớn hơn 0.',
            'max_per_user.max' => 'Giới hạn mỗi người không được vượt quá 100.',
            'reason.max' => 'Lý do không được vượt quá 500 ký tự.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Validate sale_price < original_price
        $original = $this->input('original_price') ?? $this->route('item')->original_price ?? 0;
        $sale = $this->input('sale_price');

        if ($sale !== null && $original > 0 && (float) $sale >= (float) $original) {
            $this->merge(['sale_price' => (float) $original * 0.8]); // Auto adjust
        }
    }

    protected function flashSale(): FlashSale
    {
        return $this->route('flashSale') ?? $this->route('flash_sale');
    }
}
