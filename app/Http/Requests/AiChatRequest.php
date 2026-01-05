<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:5', 'max:200'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Bạn hãy nhập câu hỏi trước khi gửi.',
            'question.min' => 'Nội dung câu hỏi quá ngắn, vui lòng mô tả rõ hơn.',
            'question.max' => 'Nội dung câu hỏi tối đa 200 ký tự.',
            'history.array' => 'Lịch sử hội thoại không hợp lệ.',
            'history.max' => 'Chỉ giữ lại tối đa 10 trao đổi gần nhất.',
        ];
    }

    /**
     * @return array<int, array{role:string, content:string}>
     */
    public function sanitizedHistory(): array
    {
        $history = $this->validated('history') ?? [];

        return collect($history)
            ->filter(fn ($item) => isset($item['role'], $item['content']))
            ->map(fn ($item) => [
                'role' => $item['role'],
                'content' => mb_substr(trim((string) $item['content']), 0, 200),
            ])
            ->values()
            ->all();
    }
}
