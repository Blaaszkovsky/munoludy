<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wymusza brak cache'owania odpowiedzi — w przeglądarce i na CDN (Cloudflare).
 *
 * Stosowane na trasach zależnych od sesji (głosowanie, rejestracja): bez tego
 * Cloudflare potrafi zaserwować z brzegu starą wersję strony (np. podsumowanie
 * bez komunikatów walidacji), a także — co istotne dla bezpieczeństwa — nie
 * wolno trzymać na CDN stron chronionych kodem dostępu / draftu głosów.
 */
class NoCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        // Targeted directive — Cloudflare honoruje to nawet przy regule "Cache Everything".
        $response->headers->set('CDN-Cache-Control', 'no-store');

        return $response;
    }
}
