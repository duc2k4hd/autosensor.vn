<?php

namespace App\Http\Requests\Admin\FlashSale;

use App\Models\FlashSale;
use Illuminate\Foundation\Http\FormRequest;

class ImportFlashSaleItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->flashSale());
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File import là bắt buộc.',
            'file.file' => 'File không hợp lệ.',
            'file.mimes' => 'File phải có định dạng: xlsx, xls, csv.',
            'file.max' => 'Kích thước file không được vượt quá 10MB.',
        ];
    }

    protected function flashSale(): FlashSale
    {
        return $this->route('flashSale') ?? $this->route('flash_sale');
    }
}
