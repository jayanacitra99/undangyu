<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Registered in bootstrap/app.php with the "admin" prefix and the
| web + auth + role:super-admin|admin|support middleware stack.
| Controllers live in App\Http\Controllers\Admin.
|
*/

Route::get('/', fn () => response()->json(['surface' => 'admin', 'user' => auth()->id()]))
    ->name('admin.home');

Route::get('/ping', fn () => response()->json(['surface' => 'admin']))->name('admin.ping');
