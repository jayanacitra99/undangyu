<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Enums\WishStatus;
use App\Exports\WishesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ModerateWishesRequest;
use App\Models\Invitation;
use App\Models\Wish;
use App\Services\Wishes\WishFeed;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The moderation queue (29.4).
 *
 * Approve, reject, pin, delete — one at a time or over a selection. The
 * default view is what needs a decision: a client opens this because something
 * is waiting, not to admire the approved ones.
 */
final class WishController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request, Invitation $invitation): View
    {
        Gate::authorize('view', $invitation);

        $status = WishStatus::tryFrom($request->string('status')->toString());

        $wishes = $invitation->wishes()
            ->with('guest:id,name')
            ->search($request->string('search')->toString())
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_pinned')
            ->latest('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('client.wishes.index', [
            'invitation' => $invitation,
            'wishes' => $wishes,
            'statuses' => WishStatus::cases(),
            'counts' => $this->counts($invitation),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status?->value,
            ],
        ]);
    }

    /**
     * One action over a selection (29.4). The ids are filtered through the
     * invitation's own relation, so a crafted payload moderates nothing that
     * is not the client's.
     */
    public function moderate(
        ModerateWishesRequest $request,
        Invitation $invitation,
        WishFeed $feed,
    ): RedirectResponse {
        $wishes = $invitation->wishes()->whereKey($request->ids());
        $action = $request->action();

        $affected = match ($action) {
            'approve' => $wishes->update(['status' => WishStatus::Approved]),
            'reject' => $wishes->update(['status' => WishStatus::Rejected, 'is_pinned' => false]),
            'pin' => $wishes->update(['is_pinned' => true, 'status' => WishStatus::Approved]),
            'unpin' => $wishes->update(['is_pinned' => false]),
            'delete' => $wishes->delete(),
            // Unreachable: the request validates against the same allowlist.
            // Kept because "the form said so" is not a reason to run an
            // unknown action against a client's guestbook.
            default => abort(422),
        };

        // Every one of those changes what the feed should show, and the feed
        // is cached for a minute.
        $feed->forget($invitation);

        return back()->with('status', __(':count ucapan diperbarui.', ['count' => $affected]));
    }

    public function export(Invitation $invitation): BinaryFileResponse
    {
        Gate::authorize('view', $invitation);

        return Excel::download(
            new WishesExport($invitation),
            'ucapan-'.$invitation->slug.'.xlsx',
            ExcelFormat::XLSX,
        );
    }

    /**
     * How many are waiting, showing and rejected — the tabs across the top.
     *
     * @return array<string, int>
     */
    private function counts(Invitation $invitation): array
    {
        $counts = $invitation->wishes()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts[WishStatus::Pending->value] ?? 0),
            'approved' => (int) ($counts[WishStatus::Approved->value] ?? 0),
            'rejected' => (int) ($counts[WishStatus::Rejected->value] ?? 0),
        ];
    }

    /**
     * The per-row delete. Same effect as the bulk one over a selection of
     * one, and it clears the feed cache for the same reason.
     */
    public function destroy(Invitation $invitation, Wish $wish, WishFeed $feed): RedirectResponse
    {
        Gate::authorize('delete', $wish);

        abort_unless($wish->invitation_id === $invitation->getKey(), 404);

        $wish->delete();
        $feed->forget($invitation);

        return back()->with('status', __('Ucapan dihapus.'));
    }
}
