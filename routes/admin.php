<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
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

Route::get('/', DashboardController::class)->name('admin.dashboard');

Route::get('/ping', fn () => response()->json(['surface' => 'admin']))->name('admin.ping');
