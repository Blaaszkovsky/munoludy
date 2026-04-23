<?php

use App\Services\Content\TokenGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates 40-char link hash', function () {
    $gen = new TokenGenerator();
    expect(strlen($gen->uniqueLinkHash()))->toBe(40);
});

it('generates 6-digit code padded with zeros', function () {
    $gen = new TokenGenerator();
    $code = $gen->sixDigitCode();
    expect(strlen($code))->toBe(6);
    expect($code)->toMatch('/^\d{6}$/');
});
