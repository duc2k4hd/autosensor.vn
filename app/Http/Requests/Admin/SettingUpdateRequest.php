<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $settingId = $this->route('setting')?->id;

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('settings', 'key')->ignore($settingId),
                'regex:/^[a-z0-9_]+$/i',
            ],
            'value' => [
                'nullable',
                'string',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Setting::TYPES),
            ],
            'group' => [
                'nullable',
                'string',
                'max:255',
            ],
            'label' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_public' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'Key là bắt buộc.',
            'key.unique' => 'Key này đã tồn tại.',
            'key.regex' => 'Key chỉ được chứa chữ cái, số và dấu gạch dưới.',
            'type.required' => 'Kiểu dữ liệu là bắt buộc.',
            'type.in' => 'Kiểu dữ liệu không hợp lệ.',
        ];
    }
}
