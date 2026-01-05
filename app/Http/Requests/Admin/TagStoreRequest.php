<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:tags,slug',
                'regex:/^[a-z0-9-]+$/i',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'entity_id' => [
                'required',
                'integer',
            ],
            'entity_type' => [
                'required',
                'string',
                Rule::in(['product', 'post']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên tag là bắt buộc.',
            'slug.unique' => 'Slug này đã tồn tại.',
            'slug.regex' => 'Slug chỉ được chứa chữ cái, số và dấu gạch ngang.',
            'entity_id.required' => 'ID entity là bắt buộc.',
            'entity_type.required' => 'Loại entity là bắt buộc.',
            'entity_type.in' => 'Loại entity không hợp lệ.',
        ];
    }

    public function prepareForValidation(): void
    {
        // Validate entity_id exists in the specified entity_type
        if ($this->has('entity_id') && $this->has('entity_type')) {
            $entityType = $this->entity_type;
            $entityId = $this->entity_id;

            if ($entityType === 'product') {
                $exists = Product::where('id', $entityId)->exists();
            } elseif ($entityType === 'post') {
                $exists = Post::where('id', $entityId)->exists();
            } else {
                $exists = false;
            }

            if (! $exists) {
                $this->merge(['entity_id' => null]);
            }
        }
    }
}
