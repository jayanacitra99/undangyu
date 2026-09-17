<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Counts one view of a published invitation (21.6).
 *
 * Hard rule 1: a public page view never writes synchronously. Five hundred
 * guests opening a link in the same minute must not each wait on a write.
 *
 * Two things make this safe to run on every view:
 *
 *   - It is a **query builder** increment, not a model save. A model save
 *     would fire InvalidatesInvitationCache and flush the payload — so the
 *     cache built for those five hundred guests would be destroyed by the
 *     first of them arriving, on every single view. The counter is not part of
 *     the payload, so nothing cached goes stale by skipping the event.
 *   - It is an atomic `increment`, so concurrent views add up instead of
 *     overwriting each other.
 */
class RecordInvitationViewJob implements ShouldQueue
{
    use Queueable;

    /**
     * A lost view count is not worth a retry storm; one more go covers a
     * blipped connection.
     */
    public int $tries = 2;

    /**
     * Addressed by uuid, which is the invitation's public identity and is
     * already in the payload — so recording a view costs the request nothing,
     * not even a lookup of the primary key.
     */
    public function __construct(public readonly string $uuid) {}

    public function handle(): void
    {
        DB::table('invitations')
            ->where('uuid', $this->uuid)
            ->increment('view_count');
    }
}
