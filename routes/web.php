<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('{path?}', IndexController::class)->where('path', '.*')->fallback();

Route::prefix('dashboard')->name('dashboard.')->middleware(['auth', 'verified'])->group(function () {
    Route::view('', 'pages::dashboard.home')->name('home');
    Route::livewire('entries/{type}', 'pages::dashboard.entries')->name('entries');
    Route::livewire('terms/{type}', 'pages::dashboard.terms')->name('terms');
});

require __DIR__.'/settings.php';
