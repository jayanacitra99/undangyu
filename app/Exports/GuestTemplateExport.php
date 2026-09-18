<?php

declare(strict_types=1);

namespace App\Exports;

use App\Imports\GuestRowsImport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The blank import template (25.2).
 *
 * Headings plus one filled example row: a client who has never used a template
 * before copies the example, and a client who has seen one still learns from it
 * that "vip" wants "ya" and that a phone number may be typed as 0812…
 *
 * The headings are GuestRowsImport::COLUMNS, so the file we hand out and the
 * file we can read cannot drift apart.
 */
class GuestTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Budi Santoso', 'Bapak', '081234567890', 'budi@contoh.com', 'Jl. Melati No. 4, Sidoarjo', 'Kantor', '2', 'tidak', 'A1', 'Teman kerja'],
        ];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return array_values(GuestRowsImport::COLUMNS);
    }

    public function title(): string
    {
        return 'Tamu';
    }
}
