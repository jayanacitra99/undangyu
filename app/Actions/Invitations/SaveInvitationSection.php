<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\SectionKey;
use App\Models\Invitation;
use App\Models\InvitationSection;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one section row (M4.10, M4.11).
 *
 * Two kinds live in this table. The built-in sections — cover, persons, events
 * and the rest — are switched on and off and retitled, but never created or
 * deleted by a client: they are what the template renders. A `custom` section
 * is the client's own block, and there may be any number of those.
 */
final class SaveInvitationSection
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Invitation $invitation,
        array $attributes,
        ?InvitationSection $section = null,
    ): InvitationSection {
        return DB::transaction(function () use ($invitation, $attributes, $section): InvitationSection {
            if ($section === null) {
                $attributes['section_key'] = SectionKey::Custom;
                $attributes['sort_order'] = ((int) ($invitation->sections()->max('sort_order') ?? -1)) + 1;

                return $invitation->sections()->create($attributes);
            }

            // The key is what the renderer dispatches on; a client editing
            // their section is changing its title and body, not turning a
            // gallery into a cover.
            unset($attributes['section_key']);

            $section->fill($attributes);
            $section->save();

            return $section->refresh();
        });
    }
}
