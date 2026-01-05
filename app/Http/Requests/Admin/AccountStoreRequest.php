<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Account fields
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:80', 'unique:accounts,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(\App\Models\Account::roles())],
            'status' => ['required', 'string', Rule::in(\App\Models\Account::statuses())],
            'logs' => ['nullable', 'string'],
            'email_verified' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],

            // Profile fields
            'phone' => ['nullable', 'string', 'max:20'],
            'fullname' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'birthday' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên là bắt buộc.',
            'name.max' => 'Tên không được vượt quá 50 ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 80 ký tự.',
            'email.unique' => 'Email đã tồn tại.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password_confirmation.required' => 'Xác nhận mật khẩu là bắt buộc.',
            'role.required' => 'Vai trò là bắt buộc.',
            'role.in' => 'Vai trò không hợp lệ.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'birthday.before' => 'Ngày sinh phải trước ngày hiện tại.',
        ];
    }
}
