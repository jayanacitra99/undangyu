<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a template sits in its publishing lifecycle (M3.3).
 *
 * Only `Published` is reachable from the public gallery. `Archived` is a
 * retirement, not a delete: invitations already pinned to the template keep
 * rendering, but nobody new can pick it.
 */
enum TemplateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
            self::Archived => 'Arsip',
        };
    }

    /**
     * The Bootstrap badge class the admin index paints the status with.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'text-bg-secondary',
            self::Published => 'text-bg-success',
            self::Archived => 'text-bg-dark',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
