<?php

use App\Services\Security\DisposableEmailChecker;
use Illuminate\Support\Facades\Storage;

it('detects disposable domain', function () {
    Storage::disk('local')->put('disposable-domains.txt', "10minutemail.com\nmailinator.com\n");
    cache()->forget('disposable_domains_set');
    $checker = new DisposableEmailChecker();
    expect($checker->isDisposable('foo@10minutemail.com'))->toBeTrue();
    expect($checker->isDisposable('FOO@MAILINATOR.COM'))->toBeTrue();
    expect($checker->isDisposable('foo@gmail.com'))->toBeFalse();
});

it('handles malformed email', function () {
    $checker = new DisposableEmailChecker();
    expect($checker->isDisposable('notanemail'))->toBeFalse();
});
