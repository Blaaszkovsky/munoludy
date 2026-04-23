<?php

use App\Enums\EditionStatus;
use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows 404 before results published', function () {
    $this->seed();
    $this->get('/wyniki')->assertNotFound();
});

it('renders results after publication', function () {
    $this->seed();
    $edition = Edition::active();
    $edition->update([
        'status' => EditionStatus::ResultsPublished,
        'results_published_at' => now(),
    ]);
    $this->get('/wyniki')->assertOk()->assertSee('Nagrody Publiczności');
});

it('renders results by slug', function () {
    $this->seed();
    $edition = Edition::active();
    $edition->update([
        'status' => EditionStatus::ResultsPublished,
        'results_published_at' => now(),
    ]);
    $this->get('/wyniki/' . $edition->slug)->assertOk()->assertSee('Nagrody Jury');
});

it('returns 404 for unknown edition slug', function () {
    $this->seed();
    $this->get('/wyniki/non-existing-edition')->assertNotFound();
});
