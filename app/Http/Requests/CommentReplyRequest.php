<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reply_content' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reply_content.required' => 'Vui lòng nhập nội dung trả lời.',
            'reply_content.min' => 'Nội dung trả lời phải có ít nhất 10 ký tự.',
            'reply_content.max' => 'Nội dung trả lời không được vượt quá 5000 ký tự.',
        ];
    }
}
