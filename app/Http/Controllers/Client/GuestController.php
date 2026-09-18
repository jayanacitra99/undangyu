<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\IndexGuestsRequest;
use App\Http\Resources\GuestGroupResource;
use App\Http\Resources\GuestImportResource;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\Guests\GuestQuota;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * The guest list page (M5.1, M5.10).
 *
 * Its own route rather than a builder tab: the builder's tabs are autosaved
 * forms over a handful of rows, and this is a paginated table with search,
 * bulk selection and a thousand rows behind it. Sharing a shell with them
 * would mean the builder loading a paginator it never uses.
 *
 * The first page is rendered into the island's props so the table has rows
 * before its first fetch; everything after that is the API.
 */
final class GuestController extends Controller
{
    public function index(IndexGuestsRequest $request, Invitation $invitation, GuestQuota $quota): View
    {
        // `view`, not `update`: a suspended invitation's guest list stays
        // readable by its owner. The controls are what get disabled.
        Gate::authorize('view', $invitation);

        $guests = $request->guestsQuery()->paginate($request->perPage());

        return view('client.guests.index', [
            'invitation' => $invitation,
            'guests' => GuestResource::collection($guests)->resolve(),
            'pagination' => [
                'current_page' => $guests->currentPage(),
                'last_page' => $guests->lastPage(),
                'per_page' => $guests->perPage(),
                'total' => $guests->total(),
            ],
            'groups' => GuestGroupResource::collection(
                $invitation->guestGroups()->withCount('guests')->get()
            )->resolve(),
            'titles' => Guest::TITLES,
            // The last upload, so a client who reloads while an import runs
            // still sees its progress rather than an empty panel.
            'latestImport' => $this->latestImport($invitation),
            'quota' => $quota->summary($invitation),
            'canEdit' => Gate::allows('update', $invitation),
        ]);
    }

    /**
     * An invitation that has never been imported into has no panel state, and
     * a resource over null resolves to a row of nulls rather than to nothing.
     *
     * @return array<string, mixed>|null
     */
    private function latestImport(Invitation $invitation): ?array
    {
        $import = $invitation->guestImports()->first();

        return $import === null ? null : GuestImportResource::make($import)->resolve();
    }
}
