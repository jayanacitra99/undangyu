<?php

declare(strict_types=1);

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

// The template catalog (M3.4, M3.5). Read-only and rate limited like every
// other public endpoint; the per-template preview arrives in Session 21.
Route::middleware('throttle:60,1')->group(function (): void {
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
});
