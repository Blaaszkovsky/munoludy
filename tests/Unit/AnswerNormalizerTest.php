<?php

use App\Services\Analysis\AnswerNormalizer;

it('normalizes polish diacritics and casing', function () {
    $n = new AnswerNormalizer();
    expect($n->normalize('DJ Żółć!!'))->toBe('dj zolc');
    expect($n->normalize('  Kryptogram  '))->toBe('kryptogram');
});
