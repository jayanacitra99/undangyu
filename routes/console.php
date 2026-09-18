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

// Yesterday's numbers, once it is properly yesterday everywhere (M9.2). The
// charts read only what this writes, so a day that is not rolled up is a gap
// in the client's graph rather than a slow query.
Schedule::command('analytics:rollup')->dailyAt('00:30')->withoutOverlapping();

// The raw table is the one that grows without bound (docs/03 § 3.7). Weekly
// is often enough for a 90-day window and keeps the delete off the nightly
// path where the rollup is running.
Schedule::command('analytics:prune')->weeklyOn(1, '03:30')->withoutOverlapping();

// Invitations past their active period (M4.18). Early morning, so a guest
// opening a link the night an invitation lapses still sees it — the renderer
// treats a past expires_at as expired anyway, this only makes it durable.
Schedule::command('invitations:expire')->dailyAt('02:00')->withoutOverlapping();
