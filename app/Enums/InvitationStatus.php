<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an invitation sits in its life (docs/03 § 3.4).
 *
 * `draft` is what provisioning creates; `published` is the only state the
 * public renderer serves. `expired` is the active window closing, `suspended`
 * is an admin decision.
 */
enum InvitationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Expired = 'expired';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
            self::Expired => 'Kedaluwarsa',
            self::Suspended => 'Ditangguhkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'text-bg-secondary',
            self::Published => 'text-bg-success',
            self::Expired => 'text-bg-warning',
            self::Suspended => 'text-bg-danger',
        };
    }

    /**
     * May the public renderer serve this invitation at all? Visibility then
     * decides who gets past the door.
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
