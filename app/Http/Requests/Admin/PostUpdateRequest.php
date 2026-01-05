<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PostUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                function ($attribute, $value, $fail) use ($post) {
                    if ($value && \App\Models\Post::where('slug', $value)
                        ->where('id', '!=', $post->id)
                        ->exists()) {
                        $fail('Slug đã tồn tại.');
                    }
                },
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,pending,published,archived'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'tag_names' => ['nullable', 'string'],
            'image_ids' => ['nullable', 'array'],
            'image_ids.*' => ['string'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_canonical' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề là bắt buộc.',
            'title.min' => 'Tiêu đề phải có ít nhất 3 ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'slug.regex' => 'Slug không hợp lệ. Chỉ chấp nhận chữ thường, số và dấu gạch ngang.',
            'excerpt.max' => 'Tóm tắt không được vượt quá 500 ký tự.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'account_id.exists' => 'Tài khoản không tồn tại.',
            'tag_ids.array' => 'Tags phải là mảng.',
            'tag_ids.*.integer' => 'Tag ID phải là số nguyên.',
            'tag_ids.*.exists' => 'Tag không tồn tại.',
            'image_ids.array' => 'Ảnh phải là mảng.',
            'published_at.date' => 'Ngày xuất bản không hợp lệ.',
            'meta_title.max' => 'Meta title không được vượt quá 255 ký tự.',
            'meta_description.max' => 'Meta description không được vượt quá 500 ký tự.',
            'meta_canonical.max' => 'Canonical URL không được vượt quá 500 ký tự.',
        ];
    }
}
