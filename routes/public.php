<?php

declare(strict_types=1);

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
    Route::get('/templates/{slug}', [TemplateGalleryController::class, 'show'])->name('templates.show');

    // The pricing table (M2.3), generated from the packages' feature flags.
    Route::get('/harga', PricingController::class)->name('pricing.index');
});

Route::get('/ping', fn () => response()->json(['surface' => 'public']))->name('public.ping');
