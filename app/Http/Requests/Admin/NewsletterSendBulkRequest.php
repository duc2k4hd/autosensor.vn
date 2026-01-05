<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterSendBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:190'],
            'template' => ['required', 'string', 'max:190'],
            'content' => ['nullable', 'string'],
            'cta_url' => ['nullable', 'url', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'footer' => ['nullable', 'string'],
            'filter_status' => ['nullable', 'string', 'in:all,pending,subscribed,unsubscribed'],
            'filter_source' => ['nullable', 'string', 'max:100'],
            'filter_date_from' => ['nullable', 'date'],
            'filter_date_to' => ['nullable', 'date', 'after_or_equal:filter_date_from'],
            'subscription_ids' => ['nullable', 'array'],
            'subscription_ids.*' => ['integer'],
            'email_account_id' => ['nullable', 'integer'],
        ];
    }
}
