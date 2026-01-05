<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BannerStoreRequest extends FormRequest
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
        $positions = array_keys(config('banners.positions', []));
        $maxSize = config('banners.image.max_size', 5120);

        return [
            'title' => 'required|string|max:255',
            'link' => 'nullable|url|max:500',
            'position' => ['required', 'string', 'in:'.implode(',', $positions)],
            'image_desktop' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxSize],
            'image_mobile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxSize],
            'description' => 'nullable|string|max:1000',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'target' => 'nullable|string|in:_blank,_self',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề banner.',
            'position.required' => 'Vui lòng chọn vị trí hiển thị.',
            'position.in' => 'Vị trí hiển thị không hợp lệ.',
            'image_desktop.required' => 'Vui lòng chọn ảnh desktop.',
            'image_desktop.image' => 'File phải là hình ảnh.',
            'image_desktop.mimes' => 'Hình ảnh phải có định dạng: jpg, jpeg, png, webp.',
            'image_desktop.max' => 'Kích thước hình ảnh không được vượt quá '.config('banners.image.max_size', 5120).'KB.',
            'image_mobile.image' => 'File phải là hình ảnh.',
            'image_mobile.mimes' => 'Hình ảnh phải có định dạng: jpg, jpeg, png, webp.',
            'image_mobile.max' => 'Kích thước hình ảnh không được vượt quá '.config('banners.image.max_size', 5120).'KB.',
            'link.url' => 'Liên kết không hợp lệ.',
            'end_at.after_or_equal' => 'Thời gian kết thúc phải sau hoặc bằng thời gian bắt đầu.',
            'target.in' => 'Target không hợp lệ.',
            'order.min' => 'Thứ tự hiển thị phải lớn hơn hoặc bằng 0.',
        ];
    }
}
