<?php

declare(strict_types=1);

use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\GuestGroupController;
use App\Http\Controllers\Api\GuestImportController;
use App\Http\Controllers\Api\GuestMessageController;
use App\Http\Controllers\Api\InvitationBuilderController;
use App\Http\Controllers\Api\InvitationEventController;
use App\Http\Controllers\Api\InvitationGiftController;
use App\Http\Controllers\Api\InvitationMediaController;
use App\Http\Controllers\Api\InvitationPersonController;
use App\Http\Controllers\Api\InvitationSectionController;
use App\Http\Controllers\Api\InvitationStoryController;
use App\Http\Controllers\Api\MessageTemplateController;
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

    // Gallery (18.2, 18.4-18.6). Uploads are throttled harder than the rest:
    // each one writes a file and queues a conversion job.
    Route::post('/invitations/{invitation}/media', [InvitationMediaController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('api.media.store');
    Route::post('/invitations/{invitation}/media/embedded', [InvitationMediaController::class, 'storeEmbedded'])
        ->name('api.media.embedded');
    Route::post('/invitations/{invitation}/media/reorder', [InvitationMediaController::class, 'reorder'])
        ->name('api.media.reorder');
    Route::patch('/media/{media}', [InvitationMediaController::class, 'update'])
        ->name('api.media.update');
    Route::post('/media/{media}/cover', [InvitationMediaController::class, 'cover'])
        ->name('api.media.cover');
    Route::delete('/media/{media}', [InvitationMediaController::class, 'destroy'])
        ->name('api.media.destroy');

    // Gifts (19.1).
    Route::post('/invitations/{invitation}/gifts', [InvitationGiftController::class, 'store'])
        ->name('api.gifts.store');
    Route::post('/invitations/{invitation}/gifts/reorder', [InvitationGiftController::class, 'reorder'])
        ->name('api.gifts.reorder');
    Route::patch('/gifts/{gift}', [InvitationGiftController::class, 'update'])
        ->name('api.gifts.update');
    Route::delete('/gifts/{gift}', [InvitationGiftController::class, 'destroy'])
        ->name('api.gifts.destroy');
    Route::post('/gifts/{gift}/image', [InvitationGiftController::class, 'image'])
        ->name('api.gifts.image');

    // Story timeline (19.2).
    Route::post('/invitations/{invitation}/stories', [InvitationStoryController::class, 'store'])
        ->name('api.stories.store');
    Route::post('/invitations/{invitation}/stories/reorder', [InvitationStoryController::class, 'reorder'])
        ->name('api.stories.reorder');
    Route::patch('/stories/{story}', [InvitationStoryController::class, 'update'])
        ->name('api.stories.update');
    Route::delete('/stories/{story}', [InvitationStoryController::class, 'destroy'])
        ->name('api.stories.destroy');
    Route::post('/stories/{story}/image', [InvitationStoryController::class, 'image'])
        ->name('api.stories.image');

    // Sections (19.3). Only a custom section is created or deleted; the rest
    // are reordered, retitled and hidden.
    Route::post('/invitations/{invitation}/sections', [InvitationSectionController::class, 'store'])
        ->name('api.sections.store');
    Route::post('/invitations/{invitation}/sections/reorder', [InvitationSectionController::class, 'reorder'])
        ->name('api.sections.reorder');
    Route::patch('/sections/{section}', [InvitationSectionController::class, 'update'])
        ->name('api.sections.update');
    Route::delete('/sections/{section}', [InvitationSectionController::class, 'destroy'])
        ->name('api.sections.destroy');

    // Guests (24.3, 24.4). The listing is a read of the invitation, so it is
    // available on a suspended invitation too; everything that writes is not.
    Route::get('/invitations/{invitation}/guests', [GuestController::class, 'index'])
        ->name('api.guests.index');
    Route::post('/invitations/{invitation}/guests', [GuestController::class, 'store'])
        ->name('api.guests.store');
    Route::patch('/guests/{guest}', [GuestController::class, 'update'])
        ->name('api.guests.update');
    Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])
        ->name('api.guests.destroy');

    // Bulk operations are throttled below the rest: each one touches up to 500
    // rows, and the table fires them from a checkbox selection.
    Route::post('/invitations/{invitation}/guests/bulk-delete', [GuestController::class, 'bulkDestroy'])
        ->middleware('throttle:30,1')
        ->name('api.guests.bulk-delete');
    Route::post('/invitations/{invitation}/guests/bulk-group', [GuestController::class, 'bulkAssignGroup'])
        ->middleware('throttle:30,1')
        ->name('api.guests.bulk-group');

    // Message templates and the share workflow (26.2-26.5). Resolving is a
    // read that runs over a selection, so it is a POST with a body rather than
    // a GET with 400 ids in the query string.
    Route::get('/invitations/{invitation}/message-templates', [MessageTemplateController::class, 'index'])
        ->name('api.message-templates.index');
    Route::post('/invitations/{invitation}/message-templates', [MessageTemplateController::class, 'store'])
        ->name('api.message-templates.store');
    Route::patch('/message-templates/{template}', [MessageTemplateController::class, 'update'])
        ->name('api.message-templates.update');
    Route::delete('/message-templates/{template}', [MessageTemplateController::class, 'destroy'])
        ->name('api.message-templates.destroy');

    Route::post('/invitations/{invitation}/messages/resolve', [GuestMessageController::class, 'resolve'])
        // The editor calls this on every keystroke of the preview, debounced.
        ->middleware('throttle:120,1')
        ->name('api.messages.resolve');
    Route::post('/invitations/{invitation}/guests/mark-sent', [GuestMessageController::class, 'markSent'])
        ->middleware('throttle:60,1')
        ->name('api.guests.mark-sent');

    // Guest import (25.3, 25.5). The upload is throttled hardest of all: each
    // one stores a file and queues a job that writes hundreds of rows.
    Route::post('/invitations/{invitation}/guest-imports', [GuestImportController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api.guest-imports.store');
    // Polled every couple of seconds while an import runs, so it carries a
    // ceiling of its own rather than eating the shared API budget.
    Route::get('/guest-imports/{import}', [GuestImportController::class, 'show'])
        ->middleware('throttle:120,1')
        ->name('api.guest-imports.show');

    // Guest groups (24.5).
    Route::post('/invitations/{invitation}/guest-groups', [GuestGroupController::class, 'store'])
        ->name('api.guest-groups.store');
    Route::patch('/guest-groups/{group}', [GuestGroupController::class, 'update'])
        ->name('api.guest-groups.update');
    Route::delete('/guest-groups/{group}', [GuestGroupController::class, 'destroy'])
        ->name('api.guest-groups.destroy');
});
