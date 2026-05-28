<?php

use App\Http\Controllers\Public\JuryVoteController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\ResultsController;
use App\Http\Controllers\Public\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('landing')->middleware('nocache');

/*
 * Bootstrap tokenu CSRF — odporny na regułę Cloudflare "Cache Everything".
 *
 * Problem: "/" bywa serwowane z cache krawędziowego CF (cf-cache-status: HIT).
 * CF wycina wtedy Set-Cookie sesji i podaje WSZYSTKIM jeden, współdzielony
 * token CSRF wpieczony w HTML. Vary: User-Agent sprawia, że cała populacja
 * Safari/iOS (UA niemal identyczne) spada na jeden wpis cache → wspólny,
 * przeterminowany token → masowe HTTP 419 wyłącznie na macOS/iOS.
 *
 * Sam `nocache` tego nie naprawia: reguła "Cache Everything" nadpisuje
 * nagłówki origin. Rozwiązanie: ten endpoint jest pobierany przez JS z
 * UNIKALNYM query stringiem (?_=timestamp_rand), więc klucz cache CF nigdy
 * się nie powtarza → zawsze MISS → zawsze origin. Na MISS Set-Cookie nie jest
 * obcinany, więc ustawiana jest świeża sesja, a zwrócony token do niej pasuje.
 */
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->name('csrf-token')
    ->middleware('nocache');

Route::post('/rejestracja', [LandingController::class, 'register'])
    ->name('register')
    ->middleware(['throttle:registration', 'nocache']);

Route::prefix('jury/{hash}')->controller(JuryVoteController::class)->middleware('nocache')->group(function () {
    Route::get('/', 'start')->name('jury.vote.start');
    Route::post('/weryfikacja', 'verifyEmail')->middleware('throttle:vote-code')->name('jury.vote.verify-email');
});

Route::prefix('glosowanie/{hash}')->controller(VoteController::class)->middleware('nocache')->group(function () {
    Route::get('/', 'start')->name('vote.start');
    Route::post('/kod', 'verifyCode')->middleware('throttle:vote-code')->name('vote.verify-code');
    Route::get('/krok/{n}', 'step')->name('vote.step')->whereNumber('n');
    Route::post('/krok/{n}', 'saveStep')->name('vote.save-step')->whereNumber('n');
    Route::get('/podsumowanie', 'summary')->name('vote.summary');
    Route::post('/wyslij', 'submit')->name('vote.submit');
    Route::get('/dziekujemy', 'thankYou')->name('vote.thank-you');
});

Route::get('/wyniki/podglad/{editionSlug}', [ResultsController::class, 'preview'])
    ->middleware(['auth', 'nocache'])
    ->name('results.preview');

Route::get('/wyniki/{editionSlug?}', [ResultsController::class, 'index'])->name('results');
