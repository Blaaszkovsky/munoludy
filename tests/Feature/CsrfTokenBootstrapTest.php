<?php

/*
 * Regresja błędu HTTP 419 na macOS/iOS.
 *
 * Przyczyna źródłowa: "/" było serwowane z cache krawędziowego Cloudflare
 * ("Cache Everything"), który obcinał Set-Cookie sesji i podawał wszystkim
 * userom Safari/iOS jeden, współdzielony token CSRF wpieczony w HTML
 * (Vary: User-Agent + niemal identyczne UA Safari => jeden wpis cache).
 *
 * Obejście: formularz pobiera świeży token z /csrf-token (wołany z unikalnym
 * query stringiem przez JS). Ten endpoint MUSI być niecache'owalny i musi
 * zwracać token zgodny z sesją żądającego.
 *
 * Uwaga: pełny scenariusz 419 zależy od Cloudflare i jest nieodtwarzalny w
 * PHPUnit (middleware CSRF jest w testach wyłączony). Te testy pilnują
 * niezmienników, na których stoi obejście.
 */

it('serwuje token CSRF z niecache\'owalnego endpointu', function () {
    $response = $this->get('/csrf-token');

    $response->assertOk();
    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    $cacheControl = strtolower((string) $response->headers->get('Cache-Control'));
    expect($cacheControl)->toContain('no-store');
    expect(strtolower((string) $response->headers->get('CDN-Cache-Control')))
        ->toContain('no-store'); // dyrektywa, którą honoruje Cloudflare
});

it('zwraca token zgodny z tokenem sesji żądającego', function () {
    $this->get('/csrf-token')
        ->assertOk()
        ->assertExactJson(['token' => csrf_token()]);
});
