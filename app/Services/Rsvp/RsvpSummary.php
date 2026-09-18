<?php

declare(strict_types=1);

namespace App\Services\Rsvp;

use App\Enums\RsvpAttendance;
use App\Models\Invitation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The numbers a client plans a wedding with (28.4).
 *
 * Aggregated in the database rather than by loading five hundred rows and
 * counting them in PHP — the dashboard is opened dozens of times a day in the
 * last week before an event.
 *
 * The query builder rather than the model: these are grouped counts, not
 * invitations' worth of Rsvp objects, and hydrating models to read two summed
 * columns off them is work nobody asked for.
 *
 * Only `yes` contributes to the head count. `maybe` is shown beside it and
 * never folded in: a caterer given an optimistic number bills for it.
 */
final class RsvpSummary
{
    /**
     * @return array{
     *     yes: int, no: int, maybe: int, total: int, pax: int, maybe_pax: int,
     *     guests_responded: int, guests_total: int
     * }
     */
    public function totals(Invitation $invitation): array
    {
        $rows = $this->rsvps($invitation)
            ->selectRaw('attendance, COUNT(*) as answers, COALESCE(SUM(pax), 0) as pax')
            ->groupBy('attendance')
            ->get()
            ->keyBy('attendance');

        $answers = fn (RsvpAttendance $answer): int => (int) ($rows->get($answer->value)->answers ?? 0);
        $pax = fn (RsvpAttendance $answer): int => (int) ($rows->get($answer->value)->pax ?? 0);

        return [
            'yes' => $answers(RsvpAttendance::Yes),
            'no' => $answers(RsvpAttendance::No),
            'maybe' => $answers(RsvpAttendance::Maybe),
            'total' => (int) $rows->sum('answers'),
            'pax' => $pax(RsvpAttendance::Yes),
            'maybe_pax' => $pax(RsvpAttendance::Maybe),
            // How much of the list has answered at all — the number that says
            // whether it is time to chase people.
            'guests_responded' => $this->rsvps($invitation)->whereNotNull('guest_id')->distinct()->count('guest_id'),
            'guests_total' => $invitation->guests()->count(),
        ];
    }

    /**
     * Per event session: which occasion people are coming to, for an
     * invitation with an akad and a resepsi on different days.
     *
     * @return list<array{id: int|null, name: string, yes: int, pax: int, total: int}>
     */
    public function bySession(Invitation $invitation): array
    {
        $rows = $this->rsvps($invitation)
            ->selectRaw('invitation_event_id, attendance, COUNT(*) as answers, COALESCE(SUM(pax), 0) as pax')
            ->groupBy('invitation_event_id', 'attendance')
            ->get();

        $sessions = $invitation->events()
            ->get(['id', 'name'])
            ->map(fn ($event): array => $this->sessionRow(
                $rows->where('invitation_event_id', $event->id),
                (int) $event->id,
                (string) $event->name,
            ))
            ->all();

        // Answers that named no session at all — "I am coming", full stop.
        $unscoped = $rows->whereNull('invitation_event_id');

        if ($unscoped->isNotEmpty()) {
            $sessions[] = $this->sessionRow($unscoped, null, __('Semua acara'));
        }

        return $sessions;
    }

    /**
     * Per guest group, so a client can see that the office has answered and
     * the family has not. Only answers from the guest list appear: an
     * anonymous responder belongs to no group by definition.
     *
     * @return list<array{name: string, color: string|null, yes: int, pax: int, total: int}>
     */
    public function byGroup(Invitation $invitation): array
    {
        $rows = $this->rsvps($invitation)
            ->join('guests', 'guests.id', '=', 'rsvps.guest_id')
            ->leftJoin('guest_groups', 'guest_groups.id', '=', 'guests.guest_group_id')
            ->selectRaw('guest_groups.name as group_name, guest_groups.color as group_color, rsvps.attendance, COUNT(*) as answers, COALESCE(SUM(rsvps.pax), 0) as pax')
            ->groupBy('guest_groups.name', 'guest_groups.color', 'rsvps.attendance')
            ->get();

        return $rows->groupBy(fn (object $row): string => (string) ($row->group_name ?? ''))
            ->map(fn (Collection $group, string $name): array => [
                'name' => $name === '' ? __('Tanpa grup') : $name,
                'color' => $group->first()?->group_color,
                'yes' => (int) $group->where('attendance', RsvpAttendance::Yes->value)->sum('answers'),
                'pax' => (int) $group->where('attendance', RsvpAttendance::Yes->value)->sum('pax'),
                'total' => (int) $group->sum('answers'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function rsvps(Invitation $invitation): Builder
    {
        return DB::table('rsvps')->where('rsvps.invitation_id', $invitation->getKey());
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @return array{id: int|null, name: string, yes: int, pax: int, total: int}
     */
    private function sessionRow(Collection $rows, ?int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'yes' => (int) $rows->where('attendance', RsvpAttendance::Yes->value)->sum('answers'),
            'pax' => (int) $rows->where('attendance', RsvpAttendance::Yes->value)->sum('pax'),
            'total' => (int) $rows->sum('answers'),
        ];
    }
}
