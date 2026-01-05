<?php

namespace App\Http\Requests\Admin;

use App\Helpers\CategoryHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Category::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                function ($attribute, $value, $fail) {
                    if ($value && CategoryHelper::slugExistsGlobal($value)) {
                        $fail('Slug đã tồn tại trong hệ thống.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Allow empty string, 0, or null for root categories
                    if ($value === '' || $value === 0 || $value === null) {
                        return;
                    }
                    // If value is provided, it must be a valid category ID
                    if (! \App\Models\Category::where('id', $value)->exists()) {
                        $fail('Danh mục cha không tồn tại.');
                    }
                },
            ],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.min' => 'Tên danh mục phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá 150 ký tự.',
            'slug.regex' => 'Slug không hợp lệ. Chỉ chấp nhận chữ thường, số và dấu gạch ngang.',
            'description.max' => 'Mô tả không được vượt quá 5000 ký tự.',
            'image.image' => 'File phải là hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng: jpg, jpeg, png, webp.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 1MB.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
            'order.min' => 'Thứ tự phải lớn hơn hoặc bằng 0.',
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

        // Ensure slug is unique globally
        if ($this->has('slug')) {
            $this->merge([
                'slug' => CategoryHelper::generateUniqueSlugGlobal($this->input('slug')),
            ]);
        }
    }
}
