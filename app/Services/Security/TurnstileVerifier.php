<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(?string $token, ?string $ip = null): bool
    {
        $secret = config('munoludy.turnstile.secret_key');
        if (!$secret) {
            return app()->environment('local', 'testing'); // fail-open locally, fail-closed in prod
        }
        if (!$token) {
            return false;
        }
        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
            return (bool) $response->json('success');
        } catch (\Throwable $e) {
            Log::warning('Turnstile verify failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
