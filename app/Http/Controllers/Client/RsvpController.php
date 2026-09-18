<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Enums\RsvpAttendance;
use App\Exports\RsvpsExport;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Services\Rsvp\RsvpSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The client's RSVP dashboard (28.4).
 *
 * Server-rendered rather than an island: it is a table with a search box and
 * four counters, and a client refreshing it every hour in the last week wants
 * a page that is already there, not a bundle that fetches one.
 */
final class RsvpController extends Controller
{
    public const PER_PAGE = 25;

    public function index(Request $request, Invitation $invitation, RsvpSummary $summary): View
    {
        Gate::authorize('view', $invitation);

        $attendance = $request->string('attendance')->toString();
        $attendance = RsvpAttendance::tryFrom($attendance)?->value;

        $rsvps = $invitation->rsvps()
            ->with(['guest:id,name,guest_group_id', 'guest.group:id,name,color', 'event:id,name'])
            ->search($request->string('search')->toString())
            ->when($attendance !== null, fn ($query) => $query->where('attendance', $attendance))
            ->latest('responded_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('client.rsvps.index', [
            'invitation' => $invitation,
            'rsvps' => $rsvps,
            'totals' => $summary->totals($invitation),
            'sessions' => $summary->bySession($invitation),
            'groups' => $summary->byGroup($invitation),
            'attendances' => RsvpAttendance::cases(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'attendance' => $attendance,
            ],
        ]);
    }

    /**
     * The list as a spreadsheet (M6.7) — what gets handed to the caterer and
     * the usher team.
     */
    public function export(Invitation $invitation): BinaryFileResponse
    {
        Gate::authorize('view', $invitation);

        return Excel::download(
            new RsvpsExport($invitation),
            'rsvp-'.$invitation->slug.'.xlsx',
            ExcelFormat::XLSX,
        );
    }

    /**
     * Removing an answer a client knows is a duplicate or a prank. Rare, but
     * the alternative is a head count they cannot correct.
     */
    public function destroy(Invitation $invitation, Rsvp $rsvp): RedirectResponse
    {
        Gate::authorize('delete', $rsvp);

        abort_unless($rsvp->invitation_id === $invitation->getKey(), 404);

        $rsvp->delete();

        return back()->with('status', __('Konfirmasi dihapus.'));
    }
}
