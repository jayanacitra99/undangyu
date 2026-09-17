<?php

declare(strict_types=1);

use App\Http\Controllers\Public\InvitationController;
use App\Http\Controllers\Public\InvitationPreviewController;
use App\Http\Controllers\Public\InvitationUnlockController;
use App\Http\Controllers\Public\MediaController;
use App\Http\Controllers\Public\PricingController;
use App\Http\Controllers\Public\TemplateGalleryController;
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

/*
| What an invitation slug may look like: lowercase, digits, single hyphens
| between them. The same shape UpdateInvitationRequest enforces on the way in,
| so a slug that saved is a slug that routes.
|
| A variable rather than a const: this file is re-included on every
| application boot, and a file-scope constant would be redefined the second
| time — which is a fatal error, and in the test suite that is every test.
*/
$slugPattern = '[a-z0-9]([a-z0-9\-]{0,118}[a-z0-9])?';

// The template catalog (M3.4, M3.5). Read-only and rate limited like every
// other public endpoint; the per-template preview is below.
Route::middleware('throttle:60,1')->group(function () use ($slugPattern): void {
    Route::get('/templates', [TemplateGalleryController::class, 'index'])->name('templates.index');
    // The slug becomes a cache key, so it is constrained here rather than
    // letting any string through to mint entries under the templates tag.
    Route::get('/templates/{slug}', [TemplateGalleryController::class, 'show'])
        ->where('slug', '[a-z0-9]([a-z0-9\-]{0,138}[a-z0-9])?')
        ->name('templates.show');

    // The pricing table (M2.3), generated from the packages' feature flags.
    Route::get('/harga', PricingController::class)->name('pricing.index');

    // Stored media (18.2). Uploads sit on the private disk, so this route is
    // the only way one reaches a browser — and it asks whether the invitation
    // is published, or the viewer owns it, before streaming a byte.
    Route::get('/media/{media}/{variant?}', MediaController::class)
        ->where('variant', 'thumb|medium|full')
        ->name('media.show');

    // A debug endpoint is still a public endpoint (hard rule 2).
    Route::get('/ping', fn () => response()->json(['surface' => 'public']))->name('public.ping');

    // Previews (21.5). Both sit above the catch-all, and `/preview` is in the
    // reserved slug list so no invitation can ever occupy it.
    Route::get('/preview/{template:slug}', [InvitationPreviewController::class, 'template'])
        ->name('preview.template');
    Route::get('/preview/invitation/{uuid}', [InvitationPreviewController::class, 'draft'])
        ->middleware('signed')
        ->name('preview.invitation');

    // The passphrase gate (21.4). Throttled hard: this is a guessing surface.
    Route::post('/{slug}/unlock', InvitationUnlockController::class)
        ->middleware('throttle:10,1')
        ->where('slug', $slugPattern)
        ->name('invitation.unlock');
});

/*
| The published invitation (21.1). Registered LAST, and last in this file, so
| it cannot swallow a route that came before it — every other public path is
| already matched by the time a request reaches this one.
|
| The pattern keeps it to slug-shaped strings, so `/favicon.ico` and friends
| never reach the controller, and the controller refuses reserved words on top.
*/
Route::middleware('throttle:120,1')
    ->get('/{slug}', [InvitationController::class, 'show'])
    ->where('slug', $slugPattern)
    ->name('invitation.show');
