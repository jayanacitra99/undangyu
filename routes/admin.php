<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventTypeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TemplateCategoryController;
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
    Route::post('/event-types/reorder', [EventTypeController::class, 'reorder'])->name('admin.event-types.reorder');
    Route::resource('event-types', EventTypeController::class)
        ->except('show')
        ->names('admin.event-types');

    Route::post('/template-categories/reorder', [TemplateCategoryController::class, 'reorder'])->name('admin.template-categories.reorder');
    Route::resource('template-categories', TemplateCategoryController::class)
        ->except('show')
        ->names('admin.template-categories');
});

Route::get('/ping', fn () => response()->json(['surface' => 'admin']))->name('admin.ping');
