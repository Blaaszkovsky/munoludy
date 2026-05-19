<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(?string $token, ?string $ip = null): bool
    {
        $siteKey = config('munoludy.turnstile.site_key');
        $secret = config('munoludy.turnstile.secret_key');

        // Turnstile w ogóle niewłączony (brak OBU kluczy) → świadomie pomijamy
        // weryfikację (symetrycznie do frontu, który bez site_key nie pokazuje widgetu).
        if (!$siteKey && !$secret) {
            return true;
        }

        // site_key ustawiony, ale brak secret = błędna konfiguracja → fail-closed w prod.
        if (!$secret) {
            return app()->environment('local', 'testing');
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
