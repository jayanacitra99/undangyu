<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreGuestGroupRequest;
use App\Http\Requests\Client\UpdateGuestGroupRequest;
use App\Http\Resources\GuestGroupResource;
use App\Models\GuestGroup;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Guest groups behind the table's filter bar (M5.2, 24.5).
 */
final class GuestGroupController extends Controller
{
    public function store(StoreGuestGroupRequest $request, Invitation $invitation): JsonResponse
    {
        $group = $invitation->guestGroups()->create([
            ...$request->validated(),
            'sort_order' => ((int) ($invitation->guestGroups()->max('sort_order') ?? -1)) + 1,
        ]);

        return GuestGroupResource::make($group->loadCount('guests'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateGuestGroupRequest $request, GuestGroup $group): GuestGroupResource
    {
        $group->fill($request->validated())->save();

        return GuestGroupResource::make($group->loadCount('guests'));
    }

    /**
     * Deleting a group ungroups its guests rather than deleting them — the
     * foreign key is nullOnDelete, and a client tidying their labels must not
     * lose forty invitees to it.
     */
    public function destroy(GuestGroup $group): Response
    {
        Gate::authorize('delete', $group);

        $group->delete();

        return response()->noContent();
    }
}
