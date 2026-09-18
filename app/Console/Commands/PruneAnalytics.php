<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes raw views older than the retention window (M9.1, docs/03 § 3.7).
 *
 * The rollup has already read them; what is left is a table that grows by tens
 * of thousands of rows per busy weekend and is never queried again. Ninety
 * days is long enough that a client asking "what happened in March" still gets
 * an answer from the daily stats, which are kept forever and are tiny.
 *
 * Deleted in chunks. A single DELETE over a few million rows holds locks long
 * enough to be felt by the invitations being viewed while it runs.
 */
class PruneAnalytics extends Command
{
    protected $signature = 'analytics:prune {--days=90 : Keep raw views newer than this many days}';

    protected $description = 'Delete raw invitation views older than the retention window';

    public const CHUNK = 5000;

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days)->startOfDay();

        $deleted = 0;

        do {
            // Strictly older than the cutoff: a view from exactly the boundary
            // day is inside the window a client was promised.
            $batch = DB::table('invitation_views')
                ->where('viewed_at', '<', $cutoff)
                ->limit(self::CHUNK)
                ->delete();

            $deleted += $batch;
        } while ($batch === self::CHUNK);

        $this->info("{$deleted} raw view(s) older than {$cutoff->toDateString()} deleted.");

        return self::SUCCESS;
    }
}
