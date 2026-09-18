<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Support\InvitationCache;
use Illuminate\Console\Command;

/**
 * Moves published invitations past their active period to `expired` (M4.18).
 *
 * Runs daily. Unlike `orders:expire`, this cannot be one bulk statement: each
 * invitation's cached payload carries its status, so every row that changes
 * has to have its cache tag flushed or guests keep seeing a live invitation
 * served from Redis.
 *
 * The public renderer also treats a past `expires_at` as expired regardless of
 * the column, so a guest arriving between the sweep and the write never sees a
 * stale live page — this command makes the state durable, it is not the gate.
 */
class ExpireInvitations extends Command
{
    protected $signature = 'invitations:expire';

    protected $description = 'Mark published invitations past their active period as expired';

    public function handle(): int
    {
        $expired = 0;

        Invitation::acrossAllUsers()
            ->overdue()
            ->select(['id', 'uuid', 'slug', 'status', 'expires_at'])
            ->chunkById(100, function ($invitations) use (&$expired): void {
                foreach ($invitations as $invitation) {
                    $invitation->update(['status' => InvitationStatus::Expired]);

                    // The observer on Invitation flushes this too; the explicit
                    // call is here because the update above is the only reason
                    // this command exists, and a future refactor to a bulk
                    // update would silently drop the invalidation.
                    InvitationCache::flush($invitation->getKey());

                    $expired++;
                }
            });

        $this->info($expired === 0
            ? 'No invitations to expire.'
            : "Expired {$expired} invitation(s).");

        return self::SUCCESS;
    }
}
