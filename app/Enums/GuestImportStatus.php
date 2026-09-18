<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a guest import sits (docs/03 § 3.5).
 *
 * `completed` covers a file that imported 488 of 500 rows: partial success is
 * the normal outcome of a real spreadsheet, not a failure. `failed` is for the
 * file that could not be read at all.
 */
enum GuestImportStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Menunggu',
            self::Processing => 'Sedang diproses',
            self::Completed => 'Selesai',
            self::Failed => 'Gagal',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Queued => 'text-bg-secondary',
            self::Processing => 'text-bg-info',
            self::Completed => 'text-bg-success',
            self::Failed => 'text-bg-danger',
        };
    }

    /**
     * Still moving — what the progress UI polls against.
     */
    public function isRunning(): bool
    {
        return $this === self::Queued || $this === self::Processing;
    }
}
