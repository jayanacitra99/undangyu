<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client Dashboard Routes
|--------------------------------------------------------------------------
|
| Registered in bootstrap/app.php with the "dashboard" prefix and the
| web + auth + role:client|reseller middleware stack.
| Controllers live in App\Http\Controllers\Client.
|
*/

Route::get('/ping', fn () => response()->json(['surface' => 'client']))->name('client.ping');
