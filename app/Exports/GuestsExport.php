<?php

declare(strict_types=1);

namespace App\Exports;

use App\Imports\GuestRowsImport;
use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The guest list as a spreadsheet (M5.9).
 *
 * The same columns as the import template, plus the per-guest link and the
 * open counter — so an export can be edited and fed straight back in, and so a
 * client chasing non-openers can sort by the last column in Excel.
 *
 * FromQuery rather than a collection: this runs over a thousand rows and
 * Laravel Excel chunks a query.
 */
class GuestsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private readonly Invitation $invitation,
        private readonly string $invitationUrl,
    ) {}

    /**
     * @return Builder<Guest>
     */
    public function query(): Builder
    {
        return Guest::query()
            ->where('invitation_id', $this->invitation->getKey())
            ->with('group:id,name')
            ->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            ...array_values(GuestRowsImport::COLUMNS),
            'token',
            'tautan',
            'dikirim',
            'dibuka',
            'jumlah_dibuka',
        ];
    }

    /**
     * @param  Guest  $guest
     * @return list<string|int|null>
     */
    public function map($guest): array
    {
        return [
            $guest->name,
            $guest->title,
            $guest->phone,
            $guest->email,
            $guest->address,
            $guest->group?->name,
            $guest->max_pax,
            $guest->is_vip ? 'ya' : 'tidak',
            $guest->table_number,
            $guest->notes,
            $guest->token,
            $this->invitationUrl.'?to='.$guest->token,
            $guest->sent_at?->format('Y-m-d H:i'),
            $guest->opened_at?->format('Y-m-d H:i'),
            $guest->open_count,
        ];
    }

    public function title(): string
    {
        return 'Tamu';
    }
}
