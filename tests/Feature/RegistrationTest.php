<?php

use App\Models\Edition;
use App\Models\JuryMember;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    // Clear throttle and caches between tests so the POST /rejestracja throttle
    // (3 per 60 min) doesn't leak between tests.
    Cache::flush();
    Http::fake([
        '*/users/*' => Http::response(['results' => []]),
        '*/users/' => Http::response(['id' => 'uc-1']),
        '*/subscribe_to_list/' => Http::response([]),
    ]);
});

it('registers new public participant', function () {
    $response = $this->post('/rejestracja', [
        'email' => 'visitor@gmail.com',
        'privacy_consent' => '1',
        'render_ts' => time() - 3,
    ]);
    $response->assertRedirect('/');
    $participant = Participant::where('email', 'visitor@gmail.com')->first();
    expect($participant)->not->toBeNull();
    expect($participant->type->value)->toBe('public');
});

it('registers jury member correctly', function () {
    $edition = Edition::active();
    JuryMember::create(['edition_id' => $edition->id, 'email' => 'jury@gmail.com']);
    $this->post('/rejestracja', [
        'email' => 'jury@gmail.com',
        'privacy_consent' => '1',
        'render_ts' => time() - 3,
    ])->assertRedirect('/');
    expect(Participant::where('email', 'jury@gmail.com')->first()->type->value)->toBe('jury');
});

it('rejects disposable email', function () {
    \Illuminate\Support\Facades\Storage::disk('local')->put('disposable-domains.txt', "mailinator.com\n");
    cache()->forget('disposable_domains_set');
    $this->post('/rejestracja', [
        'email' => 'visitor@mailinator.com',
        'privacy_consent' => '1',
        'render_ts' => time() - 3,
    ])->assertSessionHasErrors('email');
});

it('rejects too-fast form submission (honeypot timing)', function () {
    $this->post('/rejestracja', [
        'email' => 'visitor@example.com',
        'privacy_consent' => '1',
        'render_ts' => time(),
    ])->assertSessionHasErrors('website');
});
