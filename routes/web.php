<?php

use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('landing');
Route::post('/rejestracja', [LandingController::class, 'register'])
    ->name('register')
    ->middleware(['throttle:3,60']);

Route::prefix('glosowanie/{hash}')->controller(VoteController::class)->group(function () {
    Route::get('/', 'start')->name('vote.start');
    Route::post('/kod', 'verifyCode')->middleware('throttle:5,5')->name('vote.verify-code');
    Route::get('/krok/{n}', 'step')->name('vote.step')->whereNumber('n');
    Route::post('/krok/{n}', 'saveStep')->name('vote.save-step')->whereNumber('n');
    Route::get('/podsumowanie', 'summary')->name('vote.summary');
    Route::post('/wyslij', 'submit')->name('vote.submit')->middleware('throttle:1,10');
    Route::get('/dziekujemy', 'thankYou')->name('vote.thank-you');
});

Route::get('/_smoke/layout', function () {
    return view('_smoke-layout');
})->name('smoke.layout');
