<?php

use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| The three role surfaces live in their own route files (docs/05 § 3):
|   admin.php   → /admin      super-admin | admin | support
|   client.php  → /dashboard  client | reseller
| Only the usher lands here, since a single scanner screen doesn't warrant a
| seventh route file. The real scanner arrives in Session 36.
*/
Route::get('/scanner', fn () => response()->json(['surface' => 'usher', 'user' => auth()->id()]))
    ->middleware(['auth', 'role:usher'])
    ->name('usher.scanner');

Route::middleware('auth')->group(function () {
    /*
    | Checkout sits at /checkout rather than under /dashboard: the pricing page
    | and the template gallery link straight into it, and a guest who follows
    | that link is sent to login and returned here afterwards. The controller
    | lives in the Client namespace because that is whose surface it is.
    */
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('checkout.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
