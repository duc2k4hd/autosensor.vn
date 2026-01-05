<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]{8,30}$/'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.max' => 'Họ và tên không được vượt quá 120 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không quá 150 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không quá 30 ký tự.',
            'subject.required' => 'Vui lòng chọn phân khúc quan tâm.',
            'subject.max' => 'Phân khúc quan tâm không được vượt quá 180 ký tự.',
            'message.required' => 'Vui lòng mô tả nhu cầu của bạn.',
            'message.min' => 'Nội dung cần ít nhất 20 ký tự.',
            'message.max' => 'Nội dung không được vượt quá 5000 ký tự.',
            'attachment.file' => 'Tệp đính kèm không hợp lệ.',
            'attachment.mimes' => 'Tệp đính kèm phải có định dạng: jpg, jpeg, png, pdf, doc, docx.',
            'attachment.max' => 'Tệp đính kèm tối đa 4MB.',
        ];
    }
}
