<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
