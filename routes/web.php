<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('{path?}', IndexController::class)->where('path', '.*')->fallback();

Route::prefix('dashboard')->name('dashboard.')->middleware(['auth', 'verified'])->group(function () {
    Route::view('', 'pages::dashboard.home')->name('home');
    Route::livewire('entries/{type}', 'pages::dashboard.entries')->name('entries');
    Route::livewire('entries/{type}/create', 'pages::dashboard.entry')->name('entries.create');
    Route::livewire('entries/{type}/{entry}', 'pages::dashboard.entry')->whereNumber('entry')->name('entries.edit');
    Route::livewire('terms/{type}', 'pages::dashboard.terms')->name('terms');
    Route::livewire('plugins', 'pages::dashboard.plugins')->name('plugins');
    Route::livewire('settings/{page}', 'pages::dashboard.settings')->name('settings');
});

require __DIR__.'/settings.php';
