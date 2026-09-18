<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Invitation;
use App\Models\InvitationStatsDaily;
use App\Support\DeviceType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What the client's analytics tab reads (M9.3, 30.5).
 *
 * **Only the rollup table.** The raw views table is an append-only log that a
 * busy weekend adds tens of thousands of rows to; a chart that reaches into it
 * gets slower every Saturday until it times out on the invitation that matters
 * most. Everything here selects from `invitation_stats_daily`.
 *
 * The one exception is the device breakdown, which has no daily column to
 * live in — see `devices()`, which says what it costs and why it is bounded.
 */
final class InvitationAnalytics
{
    public const DEFAULT_DAYS = 30;

    /**
     * A row per day in the window, zero-filled: a day with no views is a gap
     * in the data but not in the chart, and a line that skips missing days
     * lies about the shape of the week.
     *
     * @return list<array{date: string, views: int, unique_visitors: int, guest_opens: int, rsvp_yes: int, wishes_count: int}>
     */
    public function series(Invitation $invitation, int $days = self::DEFAULT_DAYS): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = InvitationStatsDaily::query()
            ->where('invitation_id', $invitation->getKey())
            ->where('date', '>=', $from->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn (InvitationStatsDaily $row): string => $row->date->toDateString());

        $series = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $from->copy()->addDays($offset);
            $row = $rows->get($date->toDateString());

            $series[] = [
                'date' => $date->toDateString(),
                'views' => (int) ($row->views ?? 0),
                'unique_visitors' => (int) ($row->unique_visitors ?? 0),
                'guest_opens' => (int) ($row->guest_opens ?? 0),
                'rsvp_yes' => (int) ($row->rsvp_yes ?? 0),
                'wishes_count' => (int) ($row->wishes_count ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Everything the invitation has ever recorded, from the rollup.
     *
     * @return array{views: int, unique_visitors: int, guest_opens: int, rsvp_yes: int, rsvp_no: int, rsvp_maybe: int, total_pax: int, wishes_count: int}
     */
    public function totals(Invitation $invitation): array
    {
        $row = InvitationStatsDaily::query()
            ->where('invitation_id', $invitation->getKey())
            ->selectRaw('COALESCE(SUM(views), 0) as views')
            ->selectRaw('COALESCE(SUM(unique_visitors), 0) as unique_visitors')
            ->selectRaw('COALESCE(SUM(guest_opens), 0) as guest_opens')
            ->selectRaw('COALESCE(SUM(rsvp_yes), 0) as rsvp_yes')
            ->selectRaw('COALESCE(SUM(rsvp_no), 0) as rsvp_no')
            ->selectRaw('COALESCE(SUM(rsvp_maybe), 0) as rsvp_maybe')
            ->selectRaw('COALESCE(SUM(total_pax), 0) as total_pax')
            ->selectRaw('COALESCE(SUM(wishes_count), 0) as wishes_count')
            ->first();

        return [
            'views' => (int) ($row->views ?? 0),
            'unique_visitors' => (int) ($row->unique_visitors ?? 0),
            'guest_opens' => (int) ($row->guest_opens ?? 0),
            'rsvp_yes' => (int) ($row->rsvp_yes ?? 0),
            'rsvp_no' => (int) ($row->rsvp_no ?? 0),
            'rsvp_maybe' => (int) ($row->rsvp_maybe ?? 0),
            'total_pax' => (int) ($row->total_pax ?? 0),
            'wishes_count' => (int) ($row->wishes_count ?? 0),
        ];
    }

    /**
     * The funnel a client actually reads: of the guests we know about, how
     * many opened their link, and of those, how many answered.
     *
     * Guests and answers come from their own tables rather than the rollup —
     * they are current state, not a daily event, and "how many of my 400
     * guests have opened it" is a question about now.
     *
     * @return array{guests: int, opened: int, responded: int, attending: int, pax: int}
     */
    public function funnel(Invitation $invitation): array
    {
        $guests = $invitation->guests();

        return [
            'guests' => (clone $guests)->count(),
            'opened' => (clone $guests)->whereNotNull('opened_at')->count(),
            'responded' => $invitation->rsvps()->whereNotNull('guest_id')->distinct()->count('guest_id'),
            'attending' => $invitation->rsvps()->attending()->count(),
            'pax' => (int) $invitation->rsvps()->attending()->sum('pax'),
        ];
    }

    /**
     * The device split (M9.4).
     *
     * This one does read the raw table, because there is nowhere else for it
     * to live: a daily row cannot hold a breakdown without a column per
     * device, and the rollup would then have to be migrated every time the
     * buckets change. It is bounded to the retention window by the prune, it
     * is grouped on the indexed invitation column, and it is the only query on
     * this page that touches raw views — which is the whole reason this
     * docblock exists.
     *
     * @return list<array{device: string, label: string, views: int, share: float}>
     */
    public function devices(Invitation $invitation, int $days = self::DEFAULT_DAYS): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = DB::table('invitation_views')
            ->where('invitation_id', $invitation->getKey())
            ->where('viewed_at', '>=', $from)
            ->groupBy('device_type')
            ->selectRaw('device_type, COUNT(*) as views')
            ->pluck('views', 'device_type');

        $total = (int) $rows->sum();

        if ($total === 0) {
            return [];
        }

        $devices = [];

        foreach ([...DeviceType::ALL, null] as $device) {
            $views = (int) ($rows[$device] ?? 0);

            if ($views === 0) {
                continue;
            }

            $devices[] = [
                'device' => $device ?? 'unknown',
                'label' => DeviceType::label($device),
                'views' => $views,
                'share' => round($views / $total * 100, 1),
            ];
        }

        return $devices;
    }

    /**
     * When the last rollup covered, so the page can say how fresh it is rather
     * than quietly showing a day-old number as if it were live.
     */
    public function lastRolledUpAt(Invitation $invitation): ?Carbon
    {
        $date = InvitationStatsDaily::query()
            ->where('invitation_id', $invitation->getKey())
            ->max('date');

        return $date === null ? null : Carbon::parse((string) $date);
    }
}
