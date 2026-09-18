<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invitation;
use App\Models\MessageTemplate;
use App\Models\User;

/**
 * Who may read and edit a message template (M8.2).
 *
 * Not an InvitationChildPolicy: a system template has no invitation to
 * delegate to. The rule is per scope — a system template is readable by
 * everyone and editable by nobody, an invitation's template answers to that
 * invitation, and a user's own template answers to them.
 */
class MessageTemplatePolicy
{
    public function __construct(private readonly InvitationPolicy $invitations) {}

    public function viewAny(User $user): bool
    {
        return $this->invitations->viewAny($user);
    }

    public function view(User $user, MessageTemplate $template): bool
    {
        if ($template->is_system) {
            return true;
        }

        return $this->owns($user, $template);
    }

    /**
     * Creating needs the invitation the template will belong to, passed by the
     * caller: `$user->can('create', [MessageTemplate::class, $invitation])`.
     */
    public function create(User $user, ?Invitation $invitation = null): bool
    {
        if ($invitation === null) {
            return false;
        }

        return $this->invitations->update($user, $invitation);
    }

    public function update(User $user, MessageTemplate $template): bool
    {
        // A seeded default is never edited in place. Clients copy it.
        if (! $template->isEditable()) {
            return false;
        }

        return $this->owns($user, $template);
    }

    public function delete(User $user, MessageTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    /**
     * `acrossAllUsers`, for the same reason InvitationChildPolicy uses it: a
     * policy has to be able to see the row to refuse it.
     */
    private function owns(User $user, MessageTemplate $template): bool
    {
        if ($template->invitation_id !== null) {
            $invitation = Invitation::acrossAllUsers()
                ->withTrashed()
                ->whereKey($template->invitation_id)
                ->first();

            return $invitation !== null && $this->invitations->update($user, $invitation);
        }

        return $template->user_id !== null && $template->user_id === $user->getKey();
    }
}
