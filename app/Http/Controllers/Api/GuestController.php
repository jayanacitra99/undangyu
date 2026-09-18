<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Guests\SaveGuest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\BulkAssignGuestGroupRequest;
use App\Http\Requests\Client\BulkDestroyGuestsRequest;
use App\Http\Requests\Client\IndexGuestsRequest;
use App\Http\Requests\Client\StoreGuestRequest;
use App\Http\Requests\Client\UpdateGuestRequest;
use App\Http\Resources\GuestGroupResource;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\Guests\GuestQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The guest list behind the GuestTable island (M5.1, M5.10, 24.3, 24.4).
 *
 * Every listing is paginated and filtered in the database. The table is
 * specified to survive 1000+ rows, and the way it survives them is by never
 * being sent them.
 */
final class GuestController extends Controller
{
    /**
     * `$invitation` has to be in the signature even though the request can
     * resolve it too: implicit binding substitutes a route parameter only when
     * the controller's method asks for the model, and without it the
     * FormRequest is handed the raw slug.
     */
    public function index(
        IndexGuestsRequest $request,
        Invitation $invitation,
        GuestQuota $quota,
    ): AnonymousResourceCollection {
        $guests = $request->guestsQuery()->paginate($request->perPage())->withQueryString();

        // The groups ride along with every page: their counts move whenever a
        // guest is added, deleted or reassigned, and a separate endpoint for
        // them would be a second request that is always made at the same time
        // as this one.
        return GuestResource::collection($guests)->additional([
            'meta' => [
                'quota' => $quota->summary($invitation),
                'groups' => GuestGroupResource::collection(
                    $invitation->guestGroups()->withCount('guests')->get()
                )->resolve(),
            ],
        ]);
    }

    public function store(StoreGuestRequest $request, Invitation $invitation, SaveGuest $save): JsonResponse
    {
        $guest = $save($invitation, $request->validated());

        return GuestResource::make($guest->load('group:id,name,color'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateGuestRequest $request, Guest $guest, SaveGuest $save): GuestResource
    {
        $guest->loadMissing('invitation');

        $saved = $save($guest->invitation, $request->validated(), $guest);

        return GuestResource::make($saved->load('group:id,name,color'));
    }

    public function destroy(Guest $guest): Response
    {
        Gate::authorize('delete', $guest);

        $guest->delete();

        return response()->noContent();
    }

    /**
     * Bulk delete (24.4). The ids are filtered through the invitation's own
     * relation, so a payload naming another client's guests deletes none of
     * them and reports the honest count.
     */
    public function bulkDestroy(BulkDestroyGuestsRequest $request, Invitation $invitation): JsonResponse
    {
        $deleted = $invitation->guests()->whereKey($request->ids())->delete();

        return response()->json(['deleted' => $deleted]);
    }

    /**
     * Bulk group assignment (24.5). Same filtering, same reason.
     */
    public function bulkAssignGroup(BulkAssignGuestGroupRequest $request, Invitation $invitation): JsonResponse
    {
        $updated = $invitation->guests()
            ->whereKey($request->ids())
            ->update(['guest_group_id' => $request->groupId()]);

        return response()->json(['updated' => $updated]);
    }
}
