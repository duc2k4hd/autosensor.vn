<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ContactReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Vui lòng nhập nội dung trả lời.',
            'message.min' => 'Nội dung trả lời cần ít nhất 5 ký tự.',
            'message.max' => 'Nội dung trả lời không được vượt quá 5000 ký tự.',
            'attachment.file' => 'Tệp đính kèm không hợp lệ.',
            'attachment.mimes' => 'Tệp đính kèm phải là một trong các định dạng: jpg, jpeg, png, pdf, doc, docx.',
            'attachment.max' => 'Tệp đính kèm tối đa 4MB.',
        ];
    }
}
