<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Invitation;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The RSVP list as a spreadsheet (M6.7).
 *
 * What the caterer and the usher team are handed: who is coming, how many of
 * them, which session, and which group they belong to. No ip_hash and no user
 * agent — those exist for abuse review and have no business in a file that
 * gets emailed around.
 */
class RsvpsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Invitation $invitation) {}

    /**
     * @return Builder<Rsvp>
     */
    public function query(): Builder
    {
        return Rsvp::query()
            ->where('invitation_id', $this->invitation->getKey())
            ->with(['guest:id,name,guest_group_id', 'guest.group:id,name', 'event:id,name'])
            ->orderBy('responded_at');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['nama', 'telepon', 'kehadiran', 'jumlah_orang', 'acara', 'grup', 'menu', 'pesan', 'tamu_terdaftar', 'waktu_jawab'];
    }

    /**
     * @param  Rsvp  $rsvp
     * @return list<string|int|null>
     */
    public function map($rsvp): array
    {
        return [
            $rsvp->name,
            $rsvp->phone,
            $rsvp->attendance->label(),
            $rsvp->pax,
            $rsvp->event === null ? 'Semua acara' : $rsvp->event->name,
            $rsvp->guest?->group?->name,
            $rsvp->meal_preference,
            $rsvp->notes,
            // Whether this answer came from the guest list or from someone who
            // was forwarded the link (M6.9).
            $rsvp->guest_id === null ? 'tidak' : 'ya',
            $rsvp->responded_at
                ->timezone($this->invitation->timezone)
                ->format('Y-m-d H:i'),
        ];
    }

    public function title(): string
    {
        return 'RSVP';
    }
}
