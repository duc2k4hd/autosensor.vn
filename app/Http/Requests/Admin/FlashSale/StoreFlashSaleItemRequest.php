<?php

namespace App\Http\Requests\Admin\FlashSale;

use App\Models\FlashSale;
use Illuminate\Foundation\Http\FormRequest;

class StoreFlashSaleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->flashSale());
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:1'],
            'max_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Sản phẩm là bắt buộc.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'sale_price.required' => 'Giá Flash Sale là bắt buộc.',
            'sale_price.numeric' => 'Giá Flash Sale phải là số.',
            'sale_price.min' => 'Giá Flash Sale phải lớn hơn 0.',
            'stock.required' => 'Số lượng là bắt buộc.',
            'stock.integer' => 'Số lượng phải là số nguyên.',
            'stock.min' => 'Số lượng phải lớn hơn 0.',
            'max_per_user.min' => 'Giới hạn mỗi người phải lớn hơn 0.',
            'max_per_user.max' => 'Giới hạn mỗi người không được vượt quá 100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Validate sale_price < original_price
        if ($this->has('original_price') && $this->has('sale_price')) {
            $original = (float) $this->input('original_price');
            $sale = (float) $this->input('sale_price');
            if ($sale >= $original && $original > 0) {
                $this->merge(['sale_price' => $original * 0.8]); // Auto adjust
            }
        }
    }

    protected function flashSale(): FlashSale
    {
        return $this->route('flashSale') ?? $this->route('flash_sale');
    }
}
