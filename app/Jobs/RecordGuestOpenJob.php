<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Records that a guest opened their invitation (27.3, M5.11).
 *
 * `opened_at` is the first open and never moves; `open_count` counts them all.
 * The pair is what a client chases non-openers with — "she has not opened it"
 * is actionable, "she opened it nine times" is not.
 *
 * Query builder, not a model save, for the same two reasons as the view
 * counter: an atomic increment adds up under concurrent opens, and skipping
 * the model events keeps a guest's open from flushing the payload cache that
 * five hundred other guests are being served from.
 */
class RecordGuestOpenJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly int $guestId) {}

    public function handle(): void
    {
        // whereNull, so the first open is the one that sticks: a guest who
        // opens their invitation every day for a month still has the date they
        // first saw it, which is the number a client acts on.
        DB::table('guests')
            ->where('id', $this->guestId)
            ->whereNull('opened_at')
            ->update(['opened_at' => now()]);

        DB::table('guests')
            ->where('id', $this->guestId)
            ->increment('open_count');
    }
}
