<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\ReorderInvitationChildren;
use App\Actions\Invitations\SaveInvitationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReorderInvitationChildrenRequest;
use App\Http\Requests\Client\StoreInvitationEventRequest;
use App\Http\Requests\Client\UpdateInvitationEventRequest;
use App\Http\Resources\InvitationEventResource;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Event session CRUD behind the EventSessionsEditor island (17.5).
 *
 * Every response goes back through the resource, which renders times in the
 * invitation's timezone — so the island never has to know that storage is UTC,
 * and never has to convert anything itself.
 */
final class InvitationEventController extends Controller
{
    public function store(
        StoreInvitationEventRequest $request,
        Invitation $invitation,
        SaveInvitationEvent $save,
    ): JsonResponse {
        $event = $save($invitation, $request->validated());

        // The resource reads the invitation's timezone off the inverse
        // relation; setRelation rather than a query, since it is in hand.
        $event->setRelation('invitation', $invitation);

        return InvitationEventResource::make($event)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateInvitationEventRequest $request,
        InvitationEvent $event,
        SaveInvitationEvent $save,
    ): InvitationEventResource {
        $event->loadMissing('invitation');

        $invitation = $event->invitation;

        $saved = $save($invitation, $request->validated(), $event);
        $saved->setRelation('invitation', $invitation);

        return InvitationEventResource::make($saved);
    }

    public function destroy(InvitationEvent $event): Response
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return response()->noContent();
    }

    public function reorder(
        ReorderInvitationChildrenRequest $request,
        Invitation $invitation,
        ReorderInvitationChildren $reorder,
    ): JsonResponse {
        $moved = $reorder($invitation->events(), $request->ids());

        return response()->json(['moved' => $moved]);
    }
}
