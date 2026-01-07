<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('brands', 'slug'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:1024'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'website' => ['nullable', 'url', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
            'metadata.meta_title' => ['nullable', 'string', 'max:255'],
            'metadata.meta_description' => ['nullable', 'string', 'max:500'],
            'metadata.meta_keywords' => ['nullable', 'string', 'max:255'],
            'metadata.meta_canonical' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên hãng là bắt buộc.',
            'name.min' => 'Tên hãng phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên hãng không được vượt quá 150 ký tự.',
            'slug.regex' => 'Slug không hợp lệ. Chỉ chấp nhận chữ thường, số và dấu gạch ngang.',
            'slug.unique' => 'Slug đã tồn tại.',
            'description.max' => 'Mô tả không được vượt quá 5000 ký tự.',
            'image.image' => 'File phải là hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng: jpg, jpeg, png, webp, gif.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 1MB.',
            'order.min' => 'Thứ tự phải lớn hơn hoặc bằng 0.',
            'website.url' => 'URL website không hợp lệ.',
            'country.max' => 'Tên quốc gia không được vượt quá 100 ký tự.',
            'metadata.meta_canonical.url' => 'URL canonical không hợp lệ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-generate slug if not provided
        if (! $this->has('slug') || empty($this->input('slug'))) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->input('name')),
            ]);
        }
    }
}

