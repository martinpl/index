<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('{path?}', IndexController::class)->where('path', '.*')->fallback();

Route::name('dashboard.')->middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('home');
});

require __DIR__.'/settings.php';
