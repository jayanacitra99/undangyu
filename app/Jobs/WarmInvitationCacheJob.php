<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invitation;
use App\Services\Invitations\InvitationPayloadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Builds the payload cache before the guests arrive (23.3).
 *
 * Publishing is immediately followed by the client sending the link to five
 * hundred people. Without this the first of them pays for the cold build —
 * ten queries and the whole tree — and on a bad night several of them do it
 * at once because none has finished writing the cache yet.
 */
class WarmInvitationCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly int $invitationId) {}

    public function handle(InvitationPayloadService $payloads): void
    {
        $invitation = Invitation::acrossAllUsers()->find($this->invitationId);

        if ($invitation === null) {
            return;
        }

        // Drop first: this runs after GenerateOgImageJob wrote og_image_path,
        // and a payload cached before that has a null preview image in it.
        $payloads->forget($invitation);
        $payloads->forInvitation($invitation);
    }
}
