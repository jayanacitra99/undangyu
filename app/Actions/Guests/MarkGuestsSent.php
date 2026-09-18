<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Invitation;

/**
 * Records that a client has sent these guests their invitation (26.5).
 *
 * Marked as the client works through the list, not when a message is
 * delivered: WhatsApp is opened in the client's own app and we never hear back
 * from it. `sent_at` means "I sent this one", which is exactly what a client
 * working through 400 guests needs to know when they come back tomorrow.
 *
 * Already-sent guests keep their original timestamp. Re-opening a chat is not
 * a new send, and moving the date would lose the order the list was worked in.
 */
final class MarkGuestsSent
{
    /**
     * @param  list<int>  $ids
     * @return int how many were newly marked
     */
    public function __invoke(Invitation $invitation, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return $invitation->guests()
            ->whereKey($ids)
            ->whereNull('sent_at')
            ->update(['sent_at' => now()]);
    }
}
