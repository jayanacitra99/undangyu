<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

/**
 * Writes the "Dasar" tab (16.3) and every autosave that follows it (16.4).
 *
 * The validated payload is partial by design: an autosave sends the field that
 * changed, not the whole form, so this fills in nothing and only writes keys it
 * was given.
 */
final class UpdateInvitationBasics
{
    /**
     * What the basics tab owns. Anything outside this list belongs to another
     * tab and another action — this one must not become the place every write
     * ends up.
     *
     * @var list<string>
     */
    public const FIELDS = [
        'title',
        'slug',
        'language',
        'timezone',
        'meta_title',
        'meta_description',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes): Invitation
    {
        $attributes = array_intersect_key($attributes, array_flip(self::FIELDS));

        if ($attributes === []) {
            return $invitation;
        }

        return DB::transaction(function () use ($invitation, $attributes): Invitation {
            $invitation->fill($attributes);
            $invitation->save();

            return $invitation->refresh();
        });
    }
}
