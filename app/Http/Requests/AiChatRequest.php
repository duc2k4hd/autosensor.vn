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
            'context' => ['nullable', 'array'],
            'context.page' => ['nullable', 'string', 'max:50'],
            'context.product_id' => ['nullable', 'integer', 'min:1'],
            'context.product_name' => ['nullable', 'string', 'max:255'],
            'context.product_slug' => ['nullable', 'string', 'max:255'],
            'context.category_ids' => ['nullable', 'array'],
            'context.category_ids.*' => ['integer'],
            'context.post_id' => ['nullable', 'integer', 'min:1'],
            'context.post_slug' => ['nullable', 'string', 'max:255'],
            'context.url' => ['nullable', 'string', 'max:2048'],
            'context.title' => ['nullable', 'string', 'max:255'],
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

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        $context = $this->validated('context') ?? [];

        return [
            'page' => isset($context['page']) ? (string) $context['page'] : null,
            'product_id' => isset($context['product_id']) ? (int) $context['product_id'] : null,
            'product_name' => isset($context['product_name']) ? (string) $context['product_name'] : null,
            'product_slug' => isset($context['product_slug']) ? (string) $context['product_slug'] : null,
            'category_ids' => isset($context['category_ids']) && is_array($context['category_ids'])
                ? array_values(array_filter(array_map('intval', $context['category_ids'])))
                : [],
            'post_id' => isset($context['post_id']) ? (int) $context['post_id'] : null,
            'post_slug' => isset($context['post_slug']) ? (string) $context['post_slug'] : null,
            'url' => isset($context['url']) ? (string) $context['url'] : null,
            'title' => isset($context['title']) ? (string) $context['title'] : null,
        ];
    }
}
