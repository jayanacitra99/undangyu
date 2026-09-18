<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invitation;
use App\Notifications\InvitationExpiringSoon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queues the H-7 and H-1 expiry warnings (23.6).
 *
 * Delayed notifications rather than a nightly sweep looking for invitations
 * seven days out: the delay is computed once, at publish, from this
 * invitation's own expiry. A sweep would have to re-derive it every night for
 * every invitation in the system.
 *
 * Each notification re-checks the invitation before sending — a week is long
 * enough for it to be unpublished, renewed or deleted, and a warning about an
 * invitation that is no longer expiring is noise the client will not trust
 * next time.
 */
class ScheduleExpiryWarningsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Days before expiry, per docs/04 § 4.
     *
     * @var list<int>
     */
    public const WARN_DAYS = [7, 1];

    public int $tries = 2;

    public function __construct(public readonly int $invitationId) {}

    public function handle(): void
    {
        $invitation = Invitation::acrossAllUsers()->with('user')->find($this->invitationId);

        if ($invitation?->expires_at === null) {
            return;
        }

        foreach (self::WARN_DAYS as $days) {
            $sendAt = $invitation->expires_at->copy()->subDays($days);

            // An invitation published inside the warning window has already
            // passed this date; warning about it retroactively is spam.
            if ($sendAt->isPast()) {
                continue;
            }

            $invitation->user->notify(
                (new InvitationExpiringSoon($invitation, $days))->delay($sendAt),
            );
        }
    }
}
