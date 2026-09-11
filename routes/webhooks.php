<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Registered in bootstrap/app.php with the "webhooks" prefix and the "api"
| middleware group only — no CSRF, no session. Every handler must verify the
| provider signature first, be idempotent on its gateway reference, and
| return 200 quickly by dispatching the real work to a queue.
| Controllers live in App\Http\Controllers\Webhooks.
|
*/

Route::post('/ping', fn () => response()->json(['surface' => 'webhooks']))->name('webhooks.ping');
