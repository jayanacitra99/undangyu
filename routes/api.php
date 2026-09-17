<?php

declare(strict_types=1);

use App\Http\Controllers\Api\InvitationBuilderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Registered with the "api" prefix and the api middleware stack, which is
| throttled in bootstrap/app.php.
|
| Two kinds of caller live here. The scanner app authenticates with a Sanctum
| token. The dashboard's own builder is a page in a session, so its endpoints
| add the "web" stack for the session cookie and CSRF token — a browser tab
| talking to its own backend, not a third party.
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Builder autosave (16.4). Every Vue island in Sessions 17–19 writes here.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::patch('/invitations/{invitation}', [InvitationBuilderController::class, 'update'])
        ->name('api.invitations.update');

    // Read-only and cheap, but still throttled harder than the save: it fires
    // on every keystroke in the slug field.
    Route::get('/invitations/{invitation}/slug-availability', [InvitationBuilderController::class, 'slugAvailability'])
        ->middleware('throttle:60,1')
        ->name('api.invitations.slug-availability');
});
