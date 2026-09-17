<?php

declare(strict_types=1);

use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\ManualPaymentController;
use App\Http\Controllers\Client\OrderController;
use App\Http\Controllers\Client\PaymentController;
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

// Opens a gateway transaction and sends the client to it (M2.5). Throttled:
// each hit is an outbound call to the gateway, not just a local write.
Route::post('/orders/{order}/pay', [PaymentController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('client.orders.pay');

// Bank transfer (M2.7): destination account, then proof upload. Still about a
// third of Indonesian transactions, so it is a route of its own.
Route::get('/orders/{order}/transfer', [ManualPaymentController::class, 'show'])
    ->name('client.orders.manual');
Route::post('/orders/{order}/transfer', [ManualPaymentController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('client.orders.manual.store');

Route::get('/ping', fn () => response()->json(['surface' => 'client']))->name('client.ping');
