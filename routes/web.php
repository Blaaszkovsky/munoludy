<?php

use App\Http\Controllers\Public\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('landing');
Route::post('/rejestracja', [LandingController::class, 'register'])
    ->name('register')
    ->middleware(['throttle:3,60']);

Route::get('/_smoke/layout', function () {
    return view('_smoke-layout');
})->name('smoke.layout');
