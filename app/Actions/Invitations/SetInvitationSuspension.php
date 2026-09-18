<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Services\Invitations\InvitationPayloadService;
use Illuminate\Support\Facades\DB;

/**
 * An admin taking an invitation down, or putting it back (23.5).
 *
 * Always audit-logged with a reason: a suspension is someone's wedding page
 * going dark, and "who did this and why" must survive the person who did it
 * moving on.
 *
 * Reinstating returns the invitation to published, not to draft — it was live
 * when it was suspended, and the client should not have to publish again to
 * undo somebody else's action.
 */
final readonly class SetInvitationSuspension
{
    public function __construct(private InvitationPayloadService $payloads) {}

    public function suspend(Invitation $invitation, string $reason): Invitation
    {
        return $this->apply($invitation, InvitationStatus::Suspended, 'suspended', $reason);
    }

    public function reinstate(Invitation $invitation, string $reason = ''): Invitation
    {
        return $this->apply($invitation, InvitationStatus::Published, 'reinstated', $reason);
    }

    private function apply(Invitation $invitation, InvitationStatus $status, string $event, string $reason): Invitation
    {
        $from = $invitation->status;

        $invitation = DB::transaction(function () use ($invitation, $status): Invitation {
            $invitation->update(['status' => $status]);

            return $invitation->refresh();
        });

        $this->payloads->forget($invitation);

        activity('invitation')
            ->performedOn($invitation)
            ->causedBy(auth()->user())
            ->withProperties([
                'from' => $from->value,
                'to' => $status->value,
                'reason' => $reason,
            ])
            ->event($event)
            ->log("Invitation {$event}");

        return $invitation;
    }
}
