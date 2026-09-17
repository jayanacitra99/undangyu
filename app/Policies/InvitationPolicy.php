<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;

/**
 * Who may touch an invitation (M1.4, docs/04 § 11).
 *
 * Three ways in: the owner, the reseller who built it, and staff. Everything
 * else in the invitation tree delegates here through InvitationChildPolicy, so
 * this class is the single description of what "my invitation" means.
 */
class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invitations.viewAny') || $user->can('invitations.update');
    }

    public function view(User $user, Invitation $invitation): bool
    {
        return $this->owns($user, $invitation) || $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        // Provisioning creates invitations from a paid order, not the client;
        // this covers a reseller building one by hand and the admin panel.
        return $user->can('invitations.create');
    }

    public function update(User $user, Invitation $invitation): bool
    {
        if ($invitation->status === InvitationStatus::Suspended) {
            // A suspended invitation is an admin decision. The client may still
            // read it; they may not edit their way out of it.
            return $user->can('invitations.update') && $this->isStaff($user);
        }

        return ($this->owns($user, $invitation) && $user->can('invitations.update'))
            || $this->isStaff($user);
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return ($this->owns($user, $invitation) && $user->can('invitations.delete'))
            || $this->isStaff($user);
    }

    public function restore(User $user, Invitation $invitation): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Publishing is an update plus a live URL, so it is gated separately: a
     * suspended or expired invitation must not be pushed back out by its owner.
     */
    public function publish(User $user, Invitation $invitation): bool
    {
        if (! $this->update($user, $invitation)) {
            return false;
        }

        return in_array($invitation->status, [InvitationStatus::Draft, InvitationStatus::Published], true);
    }

    /**
     * The owner, or the reseller who created it on their behalf.
     */
    private function owns(User $user, Invitation $invitation): bool
    {
        return $invitation->user_id === $user->getKey()
            || $invitation->created_by === $user->getKey();
    }

    /**
     * Staff reach every client's invitation by permission, never by ownership.
     * `super-admin` never arrives here — Gate::before answers first.
     */
    private function isStaff(User $user): bool
    {
        return $user->can('invitations.viewAny') && $user->hasAnyRole(['admin', 'support']);
    }
}
