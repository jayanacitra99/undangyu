<?php

declare(strict_types=1);

use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\OrderController;
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

Route::get('/', DashboardController::class)->name('dashboard');

// The client's own orders (M2.4). Ownership is enforced by OrderPolicy, and
// the binding resolves on order_number rather than the id.
Route::get('/orders', [OrderController::class, 'index'])->name('client.orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('client.orders.show');

Route::get('/ping', fn () => response()->json(['surface' => 'client']))->name('client.ping');
