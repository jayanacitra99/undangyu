<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Models\InvitationStory;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one timeline entry (M4.5).
 *
 * `date` is a plain date — "Juli 2019" is a memory, not an appointment — so
 * there is no timezone conversion here, unlike event sessions.
 */
final class SaveInvitationStory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?InvitationStory $story = null): InvitationStory
    {
        return DB::transaction(function () use ($invitation, $attributes, $story): InvitationStory {
            if ($story === null) {
                $attributes['sort_order'] = ((int) ($invitation->stories()->max('sort_order') ?? -1)) + 1;

                return $invitation->stories()->create($attributes);
            }

            $story->fill($attributes);
            $story->save();

            return $story->refresh();
        });
    }
}
