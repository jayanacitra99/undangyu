<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| The published invitation renderer and its public endpoints. No prefix,
| "web" middleware only. Every endpoint here must be rate limited, and
| nothing here may write to the database synchronously — queue it.
| Controllers live in App\Http\Controllers\Public.
|
*/

Route::get('/ping', fn () => response()->json(['surface' => 'public']))->name('public.ping');
