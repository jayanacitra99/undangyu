<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a piece of invitation media came from (docs/03 § 3.4).
 *
 * Only `upload` consumes storage and counts against a quota; an embedded
 * YouTube video costs us nothing.
 */
enum MediaSource: string
{
    case Upload = 'upload';
    case Embed = 'embed';
    case Library = 'library';

    public function label(): string
    {
        return match ($this) {
            self::Upload => 'Unggahan',
            self::Embed => 'Sematan',
            self::Library => 'Pustaka',
        };
    }

    public function consumesStorage(): bool
    {
        return $this === self::Upload;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
