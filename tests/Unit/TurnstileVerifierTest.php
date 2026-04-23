<?php

use App\Services\Security\TurnstileVerifier;
use Illuminate\Support\Facades\Http;

it('returns true when cloudflare responds success', function () {
    config()->set('munoludy.turnstile.secret_key', 'test-secret');
    Http::fake(['*' => Http::response(['success' => true])]);
    expect((new TurnstileVerifier())->verify('token', '1.2.3.4'))->toBeTrue();
});

it('returns false when cloudflare responds failure', function () {
    config()->set('munoludy.turnstile.secret_key', 'test-secret');
    Http::fake(['*' => Http::response(['success' => false])]);
    expect((new TurnstileVerifier())->verify('token', '1.2.3.4'))->toBeFalse();
});

it('fails open locally when no secret configured', function () {
    config()->set('munoludy.turnstile.secret_key', null);
    expect((new TurnstileVerifier())->verify('token'))->toBeTrue();
});
