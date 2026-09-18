<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Invitation;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The guestbook as a spreadsheet (M6.8).
 *
 * Couples print these. Every message goes in, including the held and rejected
 * ones with their status beside them, because the point of keeping a copy is
 * to have the whole thing.
 */
class WishesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Invitation $invitation) {}

    /**
     * @return Builder<Wish>
     */
    public function query(): Builder
    {
        return Wish::query()
            ->where('invitation_id', $this->invitation->getKey())
            ->orderBy('created_at');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['nama', 'ucapan', 'status', 'disematkan', 'waktu'];
    }

    /**
     * @param  Wish  $wish
     * @return list<string|null>
     */
    public function map($wish): array
    {
        return [
            $wish->name,
            $wish->message,
            $wish->status->label(),
            $wish->is_pinned ? 'ya' : 'tidak',
            $wish->created_at?->timezone($this->invitation->timezone)->format('Y-m-d H:i'),
        ];
    }

    public function title(): string
    {
        return 'Ucapan';
    }
}
