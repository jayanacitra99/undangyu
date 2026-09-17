<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contracts\BelongsToInvitation;
use App\Models\Invitation;
use App\Models\User;

/**
 * Every child of an invitation answers to the invitation (M1.4).
 *
 * A person, an event, a photo and a gift account are all facets of one
 * invitation; there is no case where someone may edit the guest list of an
 * invitation they cannot edit. So this delegates rather than repeating the
 * ownership rule six times — the subclasses exist only to be registered
 * against their model.
 *
 * Reading a child is `view` on the parent; writing one — creating, editing,
 * deleting — is `update` on the parent, because that is what it is.
 */
abstract class InvitationChildPolicy
{
    public function __construct(protected readonly InvitationPolicy $parent) {}

    public function viewAny(User $user): bool
    {
        return $this->parent->viewAny($user);
    }

    public function view(User $user, BelongsToInvitation $child): bool
    {
        return $this->parent->view($user, $this->invitation($child));
    }

    /**
     * Creating a child needs the invitation it will belong to, which the
     * caller passes explicitly: `$user->can('create', [InvitationPerson::class,
     * $invitation])`.
     */
    public function create(User $user, ?Invitation $invitation = null): bool
    {
        if ($invitation === null) {
            return false;
        }

        return $this->parent->update($user, $invitation);
    }

    public function update(User $user, BelongsToInvitation $child): bool
    {
        return $this->parent->update($user, $this->invitation($child));
    }

    public function delete(User $user, BelongsToInvitation $child): bool
    {
        return $this->parent->update($user, $this->invitation($child));
    }

    /**
     * Reordering is a write across several rows of one invitation, so it is
     * the parent's `update` and carries no child instance.
     */
    public function reorder(User $user, Invitation $invitation): bool
    {
        return $this->parent->update($user, $invitation);
    }

    /**
     * `withTrashed`, because a soft-deleted invitation's children must not
     * quietly become unauthorizable — the answer is still "the owner", and a
     * restore should not need a permissions story of its own.
     */
    private function invitation(BelongsToInvitation $child): Invitation
    {
        return Invitation::withTrashed()
            ->whereKey($child->invitationId())
            ->sole();
    }
}
