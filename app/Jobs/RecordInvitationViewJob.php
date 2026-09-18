<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Counts one view of a published invitation (21.6), and records it (M9.1).
 *
 * Hard rule 1: a public page view never writes synchronously. Five hundred
 * guests opening a link in the same minute must not each wait on a write.
 *
 * Two things make the counter safe to run on every view:
 *
 *   - It is a **query builder** increment, not a model save. A model save
 *     would fire InvalidatesInvitationCache and flush the payload — so the
 *     cache built for those five hundred guests would be destroyed by the
 *     first of them arriving, on every single view. The counter is not part of
 *     the payload, so nothing cached goes stale by skipping the event.
 *   - It is an atomic `increment`, so concurrent views add up instead of
 *     overwriting each other.
 *
 * The raw row alongside it is what Session 30's rollup aggregates. It is
 * written here rather than in a second job because it is the same fact: one
 * request, one insert, one increment, one trip to the queue.
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
     *
     * Everything after it describes the visit, gathered from the request that
     * is about to end: the job runs after the response, when there is no
     * request left to ask.
     */
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $ipHash = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $referrer = null,
        public readonly ?string $deviceType = null,
        public readonly ?int $guestId = null,
    ) {}

    public function handle(): void
    {
        $invitation = DB::table('invitations')
            ->where('uuid', $this->uuid)
            ->first(['id']);

        if ($invitation === null) {
            // Deleted between the response and the job. Nothing to count.
            return;
        }

        DB::table('invitations')
            ->where('id', $invitation->id)
            ->increment('view_count');

        DB::table('invitation_views')->insert([
            'invitation_id' => $invitation->id,
            'guest_id' => $this->guestId,
            'ip_hash' => $this->ipHash,
            'user_agent' => $this->userAgent,
            'referrer' => $this->referrer,
            'device_type' => $this->deviceType,
            'country' => null,
            'viewed_at' => now(),
        ]);
    }
}
