<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\FeatureKey;
use App\Models\Invitation;

/**
 * The feature toggles on `invitations.settings` (19.5, docs/03 § 3.4).
 *
 * One definition of what the JSON column may contain: its keys, their
 * defaults, and which package entitlement each one needs. The FormRequest
 * validates against this, the factory seeds from it, and the public renderer
 * reads it — so a toggle nobody sold cannot be switched on by editing a
 * payload.
 */
final class InvitationSettings
{
    public const MODERATION_MODES = [
        'auto' => 'Langsung tampil',
        'manual' => 'Tinjau dulu',
    ];

    /**
     * Every setting: its default, and the entitlement that gates it.
     *
     * A null gate means the toggle is free — turning the countdown off costs
     * nobody anything. A gated toggle can always be switched *off*; what the
     * entitlement decides is whether it may be switched on.
     *
     * @var array<string, array{default: bool|string, gate: FeatureKey|null}>
     */
    public const SCHEMA = [
        'rsvp_enabled' => ['default' => true, 'gate' => FeatureKey::Rsvp],
        'guestbook_enabled' => ['default' => true, 'gate' => FeatureKey::Guestbook],
        'guestbook_moderation' => ['default' => 'auto', 'gate' => null],
        'music_enabled' => ['default' => true, 'gate' => FeatureKey::Music],
        'music_autoplay' => ['default' => true, 'gate' => FeatureKey::Music],
        'countdown_enabled' => ['default' => true, 'gate' => null],
        'gift_enabled' => ['default' => true, 'gate' => null],
    ];

    /**
     * @return array<string, bool|string>
     */
    public static function defaults(): array
    {
        return array_map(
            fn (array $setting): bool|string => $setting['default'],
            self::SCHEMA,
        );
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::SCHEMA);
    }

    /**
     * Which settings this invitation's package actually allows to be on.
     *
     * @return array<string, bool>
     */
    public static function availability(Invitation $invitation): array
    {
        $available = [];

        foreach (self::SCHEMA as $key => $setting) {
            $available[$key] = $setting['gate'] === null
                || $invitation->entitlement($setting['gate']) === true;
        }

        return $available;
    }

    public static function isAvailable(Invitation $invitation, string $key): bool
    {
        return self::availability($invitation)[$key] ?? false;
    }

    /**
     * What the invitation currently has, with any key it is missing filled in
     * from the defaults — an older row must not make the builder render blank
     * toggles.
     *
     * @return array<string, bool|string>
     */
    public static function for(Invitation $invitation): array
    {
        return [...self::defaults(), ...array_intersect_key($invitation->settings, self::defaults())];
    }
}
