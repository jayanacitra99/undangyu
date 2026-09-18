<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\Analytics\InvitationAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The client's analytics page (M9.3, 30.5).
 *
 * Every number on it comes from InvitationAnalytics, which reads the rollup.
 * There is no query in this controller for exactly that reason.
 */
final class AnalyticsController extends Controller
{
    /**
     * @var list<int>
     */
    public const WINDOWS = [7, 30, 90];

    public function index(Request $request, Invitation $invitation, InvitationAnalytics $analytics): View
    {
        Gate::authorize('view', $invitation);

        $days = (int) $request->integer('days', InvitationAnalytics::DEFAULT_DAYS);
        $days = in_array($days, self::WINDOWS, true) ? $days : InvitationAnalytics::DEFAULT_DAYS;

        return view('client.analytics.index', [
            'invitation' => $invitation,
            'days' => $days,
            'windows' => self::WINDOWS,
            'series' => $analytics->series($invitation, $days),
            'totals' => $analytics->totals($invitation),
            'funnel' => $analytics->funnel($invitation),
            'devices' => $analytics->devices($invitation, $days),
            'lastRolledUpAt' => $analytics->lastRolledUpAt($invitation),
        ]);
    }
}
