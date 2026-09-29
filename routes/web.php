<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('{path?}', IndexController::class)->where('path', '.*')->fallback();
