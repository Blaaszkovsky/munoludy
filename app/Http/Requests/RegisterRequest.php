<?php

namespace App\Http\Requests;

use App\Services\Security\DisposableEmailChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'privacy_consent' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'website' => ['nullable', 'size:0'], // honeypot
            'cf-turnstile-response' => ['nullable', 'string'],
            'render_ts' => ['required', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (app(DisposableEmailChecker::class)->isDisposable($this->input('email'))) {
                $v->errors()->add('email', 'Ten adres e-mail nie jest akceptowany.');
            }
            $elapsed = time() - (int) $this->input('render_ts', 0);
            if ($elapsed < 2) {
                $v->errors()->add('website', 'Formularz wypełniony zbyt szybko.');
            }
        });
    }
}
