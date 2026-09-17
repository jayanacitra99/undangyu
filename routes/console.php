<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Unpaid orders lapse 24h after checkout (M2.10). Hourly is close enough:
// the deadline is what the client is shown, the sweep only writes the status.
Schedule::command('orders:expire')->hourly()->withoutOverlapping();

// Webhooks get lost (docs/04 § 5). Reporting only — applying a gateway's
// answer stays a deliberate `--apply` run until the numbers are trusted.
Schedule::command('payments:reconcile')->dailyAt('03:00')->withoutOverlapping();
