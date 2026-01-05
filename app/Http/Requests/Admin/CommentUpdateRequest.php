<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CommentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'max:5000',
            ],
            'rating' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],
            'is_approved' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Nội dung bình luận là bắt buộc.',
            'content.max' => 'Nội dung bình luận không được vượt quá 5000 ký tự.',
            'rating.integer' => 'Rating phải là số nguyên.',
            'rating.min' => 'Rating phải từ 1 đến 5.',
            'rating.max' => 'Rating phải từ 1 đến 5.',
        ];
    }
}
