<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The blocks an invitation can be built from (docs/03 § 3.4,
 * `invitation_sections.section_key`).
 *
 * An event type's `default_sections` is an ordered list of these — the sections
 * a new invitation of that type starts with.
 */
enum SectionKey: string
{
    case Cover = 'cover';
    case Persons = 'persons';
    case Events = 'events';
    case Story = 'story';
    case Gallery = 'gallery';
    case Gifts = 'gifts';
    case Rsvp = 'rsvp';
    case Guestbook = 'guestbook';
    case Quote = 'quote';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Cover => 'Sampul',
            self::Persons => 'Profil',
            self::Events => 'Acara',
            self::Story => 'Cerita',
            self::Gallery => 'Galeri',
            self::Gifts => 'Hadiah',
            self::Rsvp => 'RSVP',
            self::Guestbook => 'Buku tamu',
            self::Quote => 'Kutipan',
            self::Custom => 'Kustom',
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
