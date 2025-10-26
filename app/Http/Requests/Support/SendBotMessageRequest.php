<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class SendBotMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:500'],
            'context' => ['sometimes', 'array'],
            'context.*' => ['nullable'],
            'locale' => ['nullable', 'string', 'max:10'],
        ];
    }
}
