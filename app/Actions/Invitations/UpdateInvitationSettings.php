<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\InvitationVisibility;
use App\Models\Invitation;
use App\Support\InvitationSettings;
use Illuminate\Support\Facades\DB;

/**
 * Writes the "Pengaturan" tab (19.5).
 *
 * Two kinds of field arrive together: the JSON toggles, which merge over what
 * is stored, and the columns — visibility and password — which do not.
 *
 * A password is only meaningful while visibility is `password`; switching away
 * clears it rather than leaving a hash behind that would quietly come back
 * into force if the client switched again.
 */
final class UpdateInvitationSettings
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes): Invitation
    {
        return DB::transaction(function () use ($invitation, $attributes): Invitation {
            $settings = [
                ...InvitationSettings::for($invitation),
                ...array_intersect_key($attributes, array_flip(InvitationSettings::keys())),
            ];

            $changes = ['settings' => $settings];

            if (array_key_exists('visibility', $attributes)) {
                $visibility = $attributes['visibility'] instanceof InvitationVisibility
                    ? $attributes['visibility']
                    : InvitationVisibility::from((string) $attributes['visibility']);

                $changes['visibility'] = $visibility;

                if (! $visibility->requiresPassword()) {
                    $changes['password'] = null;
                }
            }

            // The `hashed` cast on the model does the hashing; an empty field
            // means "leave the current password alone", not "clear it".
            if (($attributes['password'] ?? null) !== null && $attributes['password'] !== '') {
                $changes['password'] = $attributes['password'];
            }

            $invitation->update($changes);

            return $invitation->refresh();
        });
    }
}
