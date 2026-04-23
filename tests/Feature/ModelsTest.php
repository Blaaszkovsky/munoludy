<?php

use App\Enums\EditionStatus;
use App\Enums\FormAudience;
use App\Models\Edition;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('has one active edition after seeding', function () {
    $this->seed();
    $edition = Edition::active();
    expect($edition)->not->toBeNull();
    expect($edition->slug)->toBe('munoludy-2026');
    expect($edition->status)->toBe(EditionStatus::Active);
});

it('returns 5 public and 7 jury questions', function () {
    $this->seed();
    $edition = Edition::active();
    expect($edition->questions()->where('audience', FormAudience::Public_->value)->count())->toBe(5);
    expect($edition->questions()->where('audience', FormAudience::Jury->value)->count())->toBe(7);
});
