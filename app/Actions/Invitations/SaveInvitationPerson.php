<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Models\InvitationPerson;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one person on an invitation (M4.3).
 *
 * A new person goes to the end of the list: the order is the client's, and a
 * card appearing in the middle of it would be a surprise.
 */
final class SaveInvitationPerson
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?InvitationPerson $person = null): InvitationPerson
    {
        return DB::transaction(function () use ($invitation, $attributes, $person): InvitationPerson {
            if ($person === null) {
                // -1 so the first person lands on 0, which is where a reorder
                // would put them: one numbering, not two.
                $attributes['sort_order'] = ((int) ($invitation->persons()->max('sort_order') ?? -1)) + 1;

                return $invitation->persons()->create($attributes);
            }

            $person->fill($attributes);
            $person->save();

            return $person->refresh();
        });
    }
}
