<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Services\Invitations\InvitationPayloadService;
use Illuminate\Support\Facades\DB;

/**
 * Published back to draft (23.5).
 *
 * `published_at` and `expires_at` are kept: the active window the client
 * bought keeps running whether or not the page is up, and clearing them would
 * hand out free days to anyone who unpublishes for a week.
 *
 * The cached payload is dropped immediately rather than waiting for the
 * observer, because the point of unpublishing is that the page stops serving
 * now.
 */
final readonly class UnpublishInvitation
{
    public function __construct(private InvitationPayloadService $payloads) {}

    public function __invoke(Invitation $invitation): Invitation
    {
        $invitation = DB::transaction(function () use ($invitation): Invitation {
            $invitation->update(['status' => InvitationStatus::Draft]);

            return $invitation->refresh();
        });

        $this->payloads->forget($invitation);

        activity('invitation')
            ->performedOn($invitation)
            ->causedBy(auth()->user())
            ->event('unpublished')
            ->log('Invitation unpublished');

        return $invitation;
    }
}
