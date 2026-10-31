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
    Route::livewire('themes', 'pages::dashboard.themes')->name('themes');
    Route::livewire('plugins', 'pages::dashboard.plugins')->name('plugins');
    Route::livewire('users', 'pages::dashboard.users')->name('users');
    Route::livewire('users/create', 'pages::dashboard.user')->name('users.create');
    Route::livewire('users/{user}', 'pages::dashboard.user')->whereNumber('user')->name('users.edit');
    Route::livewire('settings/{page}', 'pages::dashboard.settings')->name('settings');
});

require __DIR__.'/settings.php';
