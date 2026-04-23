<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_smoke/layout', function () {
    return view('_smoke-layout');
})->name('smoke.layout');
