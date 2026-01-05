<?php

namespace App\Http\Requests\Admin\FlashSale;

use App\Models\FlashSale;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->flashSale());
    }

    public function rules(): array
    {
        $flashSale = $this->flashSale();

        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tag' => ['nullable', 'string', 'max:50'],
            'start_time' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($flashSale) {
                    // Nếu flash sale đang chạy, không cho phép thay đổi thời gian
                    if ($flashSale->isActive()) {
                        $fail('Không thể thay đổi thời gian khi Flash Sale đang chạy.');
                    }
                },
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
                function ($attribute, $value, $fail) use ($flashSale) {
                    if ($flashSale->isActive()) {
                        $fail('Không thể thay đổi thời gian khi Flash Sale đang chạy.');
                    }
                },
            ],
            'status' => ['nullable', 'in:draft,active,expired'],
            'is_active' => ['nullable', 'boolean'],
            'max_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'display_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'product_add_mode' => ['nullable', 'in:auto_by_category,manual'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tên chương trình là bắt buộc.',
            'title.min' => 'Tên chương trình phải có ít nhất 3 ký tự.',
            'title.max' => 'Tên chương trình không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 5000 ký tự.',
            'banner.image' => 'File phải là hình ảnh.',
            'banner.mimes' => 'Hình ảnh phải có định dạng: jpg, jpeg, png, webp.',
            'banner.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            'tag.max' => 'Tag không được vượt quá 50 ký tự.',
            'start_time.required' => 'Thời gian bắt đầu là bắt buộc.',
            'start_time.date' => 'Thời gian bắt đầu không hợp lệ.',
            'end_time.required' => 'Thời gian kết thúc là bắt buộc.',
            'end_time.date' => 'Thời gian kết thúc không hợp lệ.',
            'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'max_per_user.min' => 'Giới hạn mỗi người phải lớn hơn 0.',
            'max_per_user.max' => 'Giới hạn mỗi người không được vượt quá 100.',
            'display_limit.min' => 'Giới hạn hiển thị phải lớn hơn 0.',
            'display_limit.max' => 'Giới hạn hiển thị không được vượt quá 200.',
            'product_add_mode.in' => 'Chế độ thêm sản phẩm không hợp lệ.',
        ];
    }

    protected function flashSale(): FlashSale
    {
        return $this->route('flashSale') ?? $this->route('flash_sale');
    }
}
