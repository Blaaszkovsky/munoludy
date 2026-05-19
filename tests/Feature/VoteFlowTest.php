<?php

use App\Models\Edition;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        '*/api/public/users/search/*' => Http::response([
            'id' => 'uc-voter-1',
            'email' => 'voter@gmail.com',
        ]),
        '*/api/public/users/uc-voter-1/add_tag/' => Http::response([]),
        '*/api/public/users/*/add_tag/' => Http::response([]),
    ]);
    $this->edition = Edition::active();
    // Ensure voting window is clearly open (avoid edge case where starts_at == now()).
    $this->edition->update([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDays(30),
    ]);
    $this->p = Participant::create([
        'edition_id' => $this->edition->id,
        'type' => 'public',
        'email' => 'voter@gmail.com',
        'user_com_user_id' => 'uc-voter-1',
        'link_hash' => str_repeat('b', 40),
        'access_code' => '123456',
    ]);
});

it('completes full vote flow for public participant', function () {
    $hash = $this->p->link_hash;

    $this->get("/glosowanie/$hash")->assertOk()->assertSee('Witaj w głosowaniu');
    $this->post("/glosowanie/$hash/kod", ['code' => '123456'])->assertRedirect();
    $this->get("/glosowanie/$hash/krok/1")->assertOk();

    $question = $this->edition->questions()->where('audience', 'public')->orderBy('order')->first();
    $this->post("/glosowanie/$hash/krok/1", [
        'answers' => [$question->id => [1 => 'DJ Hazel', 2 => 'Kamp!', 3 => 'Kryptogram', 4 => 'Tymek', 5 => 'SBTRKT']],
        'direction' => 'next',
    ])->assertRedirect();

    for ($n = 2; $n <= 5; $n++) {
        $q = $this->edition->questions()->where('audience', 'public')->orderBy('order')->skip($n - 1)->first();
        $this->post("/glosowanie/$hash/krok/$n", [
            'answers' => [$q->id => [1 => 'A', 2 => 'B']],
            'direction' => 'next',
        ]);
    }

    $this->get("/glosowanie/$hash/podsumowanie")->assertOk()->assertSee('Gotowy do wysłania');
    // Throttle state can accumulate across sub-requests — reset before the single-shot submit.
    Cache::flush();
    $this->post("/glosowanie/$hash/wyslij")->assertRedirect();

    expect($this->p->fresh()->voted_at)->not->toBeNull();
    expect($this->p->fresh()->submission->total_points)->toBeGreaterThan(0);
});

it('blocks submission when a category has no vote', function () {
    $hash = $this->p->link_hash;

    $this->post("/glosowanie/$hash/kod", ['code' => '123456'])->assertRedirect();

    // Fill only the first category, leave the remaining ones empty.
    $first = $this->edition->questions()->where('audience', 'public')->orderBy('order')->first();
    $this->post("/glosowanie/$hash/krok/1", [
        'answers' => [$first->id => [1 => 'DJ Hazel']],
        'direction' => 'next',
    ])->assertRedirect();

    Cache::flush();
    // Błędy renderowane wprost w odpowiedzi na POST (422), bez redirectu na
    // cache'owany GET — komunikaty pojawiają się niezależnie od Cloudflare.
    $this->post("/glosowanie/$hash/wyslij")
        ->assertStatus(422)
        ->assertSee('W każdej kategorii musisz oddać co najmniej jeden głos', false)
        ->assertSee('Ta kategoria wymaga co najmniej jednego głosu', false)
        ->assertHeader('CDN-Cache-Control', 'no-store');

    expect($this->p->fresh()->voted_at)->toBeNull();
});

it('rejects wrong access code', function () {
    $hash = $this->p->link_hash;
    $this->post("/glosowanie/$hash/kod", ['code' => '999999'])->assertSessionHasErrors('code');
});

it('blocks second submission and shows thank-you for participant who already voted', function () {
    $hash = $this->p->link_hash;
    $this->p->update(['voted_at' => now()]);
    $this->get("/glosowanie/$hash")
        ->assertOk()
        ->assertSee('Dziękujemy')
        ->assertDontSee('Wprowadź kod dostępu');
});
