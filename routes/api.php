<?php

declare(strict_types=1);

use App\Http\Controllers\Api\InvitationBuilderController;
use App\Http\Controllers\Api\InvitationEventController;
use App\Http\Controllers\Api\InvitationPersonController;
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

    // Persons (17.2). Creating and reordering hang off the invitation, whose
    // binding is tenant-scoped; editing one person is bound by its own id and
    // authorized by InvitationPersonPolicy.
    Route::post('/invitations/{invitation}/persons', [InvitationPersonController::class, 'store'])
        ->name('api.persons.store');
    Route::post('/invitations/{invitation}/persons/reorder', [InvitationPersonController::class, 'reorder'])
        ->name('api.persons.reorder');
    Route::patch('/persons/{person}', [InvitationPersonController::class, 'update'])
        ->name('api.persons.update');
    Route::delete('/persons/{person}', [InvitationPersonController::class, 'destroy'])
        ->name('api.persons.destroy');
    // Multipart, so POST rather than PATCH: browsers cannot send a file body
    // with a method the form element does not have.
    Route::post('/persons/{person}/photo', [InvitationPersonController::class, 'photo'])
        ->name('api.persons.photo');
    Route::delete('/persons/{person}/photo', [InvitationPersonController::class, 'deletePhoto'])
        ->name('api.persons.photo.destroy');

    // Event sessions (17.5).
    Route::post('/invitations/{invitation}/events', [InvitationEventController::class, 'store'])
        ->name('api.events.store');
    Route::post('/invitations/{invitation}/events/reorder', [InvitationEventController::class, 'reorder'])
        ->name('api.events.reorder');
    Route::patch('/events/{event}', [InvitationEventController::class, 'update'])
        ->name('api.events.update');
    Route::delete('/events/{event}', [InvitationEventController::class, 'destroy'])
        ->name('api.events.destroy');
});
