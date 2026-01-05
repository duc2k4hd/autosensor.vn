<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentStoreRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:product,post'],
            'object_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'content' => ['required', 'string', 'min:10', 'max:200'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại đối tượng.',
            'type.in' => 'Loại đối tượng không hợp lệ.',
            'object_id.required' => 'ID đối tượng không được để trống.',
            'object_id.integer' => 'ID đối tượng phải là số nguyên.',
            'object_id.min' => 'ID đối tượng không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên của bạn.',
            'name.max' => 'Tên không được vượt quá 191 ký tự.',
            'email.required' => 'Vui lòng nhập email của bạn.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 191 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            'content.min' => 'Nội dung bình luận phải có ít nhất 10 ký tự.',
            'content.max' => 'Nội dung bình luận không được vượt quá 200 ký tự.',
            'rating.required' => 'Vui lòng chọn đánh giá.',
            'rating.integer' => 'Đánh giá phải là số nguyên.',
            'rating.min' => 'Đánh giá phải từ 1 đến 5 sao.',
            'rating.max' => 'Đánh giá phải từ 1 đến 5 sao.',
        ];
    }
}
