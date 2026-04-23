<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccessCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }
}
