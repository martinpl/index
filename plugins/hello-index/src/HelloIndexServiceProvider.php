<?php

namespace HelloIndex;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HelloIndexServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::get('/hello-index', fn () => response()->json([
            'message' => 'Hello from the Hello Index plugin.',
        ]))->name('hello-index.index');
    }
}
