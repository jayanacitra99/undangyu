<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventTypeController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TemplateCategoryController;
use App\Http\Controllers\Admin\TemplateController;
use App\Models\EventType;
use App\Models\Package;
use App\Models\Template;
use App\Models\TemplateCategory;
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

Route::middleware('permission:settings.manage')->group(function (): void {
    Route::get('/settings', [SettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('admin.settings.update');
});

// Catalog taxonomy (M3.1, M3.2). Authorization is in the policies; the
// permission middleware keeps the whole area out of reach in one place.
Route::middleware('permission:templates.manage')->group(function (): void {
    // The `can:` middleware runs before the controller, and therefore before
    // the shared ReorderCatalogRequest — which cannot know which model it is
    // validating for, so it cannot authorize one.
    Route::post('/event-types/reorder', [EventTypeController::class, 'reorder'])
        ->middleware('can:reorder,'.EventType::class)
        ->name('admin.event-types.reorder');
    Route::resource('event-types', EventTypeController::class)
        ->except('show')
        ->names('admin.event-types');

    Route::post('/template-categories/reorder', [TemplateCategoryController::class, 'reorder'])
        ->middleware('can:reorder,'.TemplateCategory::class)
        ->name('admin.template-categories.reorder');
    Route::resource('template-categories', TemplateCategoryController::class)
        ->except('show')
        ->names('admin.template-categories');

    // The catalog itself (M3.3). Route-model binding resolves on the slug, so
    // the reorder endpoint is declared first to keep "reorder" off the binding.
    Route::post('/templates/reorder', [TemplateController::class, 'reorder'])
        ->middleware('can:reorder,'.Template::class)
        ->name('admin.templates.reorder');
    Route::resource('templates', TemplateController::class)
        ->except('show')
        ->names('admin.templates');
});

// Manual transfer verification (M2.7). `payments.verify` is the finance
// ability: admin holds it, support does not.
Route::middleware('permission:payments.verify')->group(function (): void {
    Route::get('/payments/pending', [PaymentVerificationController::class, 'index'])
        ->name('admin.payments.pending');
    Route::get('/payments/{payment}/proof', [PaymentVerificationController::class, 'proof'])
        ->name('admin.payments.proof');
    Route::post('/payments/{payment}/approve', [PaymentVerificationController::class, 'approve'])
        ->name('admin.payments.approve');
    Route::post('/payments/{payment}/reject', [PaymentVerificationController::class, 'reject'])
        ->name('admin.payments.reject');
});

// Packages carry their own permission (M2.1, M2.2) — a support user who may
// touch templates has no business editing what anything costs.
Route::middleware('permission:packages.manage')->group(function (): void {
    Route::post('/packages/reorder', [PackageController::class, 'reorder'])
        ->middleware('can:reorder,'.Package::class)
        ->name('admin.packages.reorder');
    Route::resource('packages', PackageController::class)
        ->except('show')
        ->names('admin.packages');
});

Route::get('/ping', fn () => response()->json(['surface' => 'admin']))->name('admin.ping');
