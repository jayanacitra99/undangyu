<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RsvpAttendance;
use App\Enums\WishStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates a day's raw activity into `invitation_stats_daily` (M9.2).
 *
 * Runs after midnight for the day that just ended, and can be re-run for any
 * date — which is the point of the unique key on (invitation_id, date): the
 * upsert overwrites a day rather than doubling it, so the first time a number
 * looks wrong the fix is to run this again, not to clean up after it.
 *
 * Four sources, one row each per invitation per day: views, RSVPs, wishes and
 * guest opens. Every one is a grouped query over a date window — no invitation
 * is loaded, and nothing here touches an Eloquent model.
 */
class RollupAnalytics extends Command
{
    protected $signature = 'analytics:rollup {--date= : The day to aggregate, defaults to yesterday}';

    protected $description = 'Aggregate raw analytics into the daily stats table';

    public function handle(): int
    {
        $date = $this->date();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $rows = [];

        foreach ($this->views($start, $end) as $invitationId => $counts) {
            $rows[$invitationId] = [
                'views' => $counts['views'],
                'unique_visitors' => $counts['unique_visitors'],
                'guest_opens' => $counts['guest_opens'],
            ];
        }

        foreach ($this->rsvps($start, $end) as $invitationId => $counts) {
            $rows[$invitationId] = [...($rows[$invitationId] ?? []), ...$counts];
        }

        foreach ($this->wishes($start, $end) as $invitationId => $count) {
            $rows[$invitationId] = [...($rows[$invitationId] ?? []), 'wishes_count' => $count];
        }

        if ($rows === []) {
            $this->info("No activity to roll up for {$date->toDateString()}.");

            return self::SUCCESS;
        }

        $now = now();

        $payload = [];

        foreach ($rows as $invitationId => $counts) {
            $payload[] = [
                'invitation_id' => $invitationId,
                'date' => $date->toDateString(),
                'views' => $counts['views'] ?? 0,
                'unique_visitors' => $counts['unique_visitors'] ?? 0,
                'rsvp_yes' => $counts['rsvp_yes'] ?? 0,
                'rsvp_no' => $counts['rsvp_no'] ?? 0,
                'rsvp_maybe' => $counts['rsvp_maybe'] ?? 0,
                'total_pax' => $counts['total_pax'] ?? 0,
                'wishes_count' => $counts['wishes_count'] ?? 0,
                'guest_opens' => $counts['guest_opens'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // upsert, so re-running a day replaces it. Chunked because a busy
        // Saturday is a few thousand invitations and one statement with a few
        // thousand rows in it is a statement some MySQL configurations refuse.
        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('invitation_stats_daily')->upsert(
                $chunk,
                ['invitation_id', 'date'],
                ['views', 'unique_visitors', 'rsvp_yes', 'rsvp_no', 'rsvp_maybe', 'total_pax', 'wishes_count', 'guest_opens', 'updated_at'],
            );
        }

        $this->info(count($payload).' invitation(s) rolled up for '.$date->toDateString().'.');

        return self::SUCCESS;
    }

    /**
     * Views, distinct visitors and how many of those views came from a guest
     * on the list rather than a shared link.
     *
     * @return array<int, array{views: int, unique_visitors: int, guest_opens: int}>
     */
    private function views(Carbon $start, Carbon $end): array
    {
        return DB::table('invitation_views')
            ->whereBetween('viewed_at', [$start, $end])
            ->groupBy('invitation_id')
            ->selectRaw('invitation_id, COUNT(*) as views, COUNT(DISTINCT ip_hash) as unique_visitors, COUNT(guest_id) as guest_opens')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->invitation_id => [
                'views' => (int) $row->views,
                'unique_visitors' => (int) $row->unique_visitors,
                'guest_opens' => (int) $row->guest_opens,
            ]])
            ->all();
    }

    /**
     * Answers given on the day, and the people they bring. Only `yes`
     * contributes pax, the same rule the dashboard counts by.
     *
     * @return array<int, array{rsvp_yes: int, rsvp_no: int, rsvp_maybe: int, total_pax: int}>
     */
    private function rsvps(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('rsvps')
            ->whereBetween('responded_at', [$start, $end])
            ->groupBy('invitation_id', 'attendance')
            ->selectRaw('invitation_id, attendance, COUNT(*) as answers, COALESCE(SUM(pax), 0) as pax')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $id = (int) $row->invitation_id;

            $totals[$id] ??= ['rsvp_yes' => 0, 'rsvp_no' => 0, 'rsvp_maybe' => 0, 'total_pax' => 0];

            $totals[$id][match ($row->attendance) {
                RsvpAttendance::Yes->value => 'rsvp_yes',
                RsvpAttendance::No->value => 'rsvp_no',
                default => 'rsvp_maybe',
            }] += (int) $row->answers;

            if ($row->attendance === RsvpAttendance::Yes->value) {
                $totals[$id]['total_pax'] += (int) $row->pax;
            }
        }

        return $totals;
    }

    /**
     * Approved wishes only: a message held for moderation is not yet something
     * that happened on the invitation.
     *
     * @return array<int, int>
     */
    private function wishes(Carbon $start, Carbon $end): array
    {
        return DB::table('wishes')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', WishStatus::Approved->value)
            ->groupBy('invitation_id')
            ->selectRaw('invitation_id, COUNT(*) as total')
            ->pluck('total', 'invitation_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    private function date(): Carbon
    {
        $option = $this->option('date');

        // Yesterday by default, because the schedule runs just after midnight
        // and a day is only complete once it is over.
        return $option === null
            ? now()->subDay()->startOfDay()
            : Carbon::parse((string) $option)->startOfDay();
    }
}
