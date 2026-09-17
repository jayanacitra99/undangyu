<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

/**
 * Writes the theme a client picked (19.4).
 *
 * The config arriving here has already been checked against the template's
 * `config_schema` by the FormRequest, so this only decides how it merges:
 * group by group, over what is already stored, so a tab that submits only
 * colours does not wipe the font choices.
 */
final class UpdateThemeConfig
{
    /**
     * @param  array<string, array<string, mixed>>  $config
     */
    public function __invoke(Invitation $invitation, array $config): Invitation
    {
        return DB::transaction(function () use ($invitation, $config): Invitation {
            $merged = $invitation->theme_config ?? [];

            foreach ($config as $group => $fields) {
                $merged[$group] = [...($merged[$group] ?? []), ...$fields];
            }

            $invitation->update(['theme_config' => $merged]);

            return $invitation->refresh();
        });
    }
}
