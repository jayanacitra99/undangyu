<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who an event is about (docs/03 § 3.3, `event_types.person_roles`).
 *
 * The roles an event type declares are the person slots its invitations offer —
 * a wedding asks for a bride and a groom, a graduation for one graduate.
 */
enum PersonRole: string
{
    case Bride = 'bride';
    case Groom = 'groom';
    case Celebrant = 'celebrant';
    case Child = 'child';
    case Parents = 'parents';
    case Graduate = 'graduate';
    case Host = 'host';

    public function label(): string
    {
        return match ($this) {
            self::Bride => 'Mempelai wanita',
            self::Groom => 'Mempelai pria',
            self::Celebrant => 'Yang berulang tahun',
            self::Child => 'Anak',
            self::Parents => 'Orang tua',
            self::Graduate => 'Wisudawan',
            self::Host => 'Penyelenggara',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
