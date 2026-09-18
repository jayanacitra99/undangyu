<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Rsvp\SubmitRsvp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreRsvpRequest;
use App\Http\Resources\RsvpResource;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\InvitationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Attendance answers from the invitation itself (28.2, 28.3).
 *
 * Public and unauthenticated, so the checks are structural rather than
 * personal: the invitation must be live, every id in the payload must belong
 * to it, and the route is rate limited per IP per invitation.
 *
 * This is the one public path that writes synchronously, and deliberately: a
 * guest tapping "Hadir" has to be told it was recorded, and a queued answer
 * they cannot see is how a guest submits four times. Hard rule 1 is about
 * page *views* — a form post is the guest asking for a write.
 */
final class RsvpController extends Controller
{
    public function store(
        StoreRsvpRequest $request,
        Invitation $publicInvitation,
        SubmitRsvp $submit,
    ): JsonResponse {
        $this->assertOpen($publicInvitation);

        $rsvp = $submit($publicInvitation, [
            ...$request->safe()->except('token'),
            'ip_hash' => Rsvp::hashIp($request->ip()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ], $request->guest());

        return RsvpResource::make($rsvp)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * What this guest said last time, so the form opens on their own answer
     * rather than an empty one (M6.3). Anonymous responders get nothing —
     * there is nothing to look them up by that is not guessable.
     */
    public function show(Request $request, Invitation $publicInvitation): JsonResponse
    {
        $this->assertOpen($publicInvitation);

        $token = trim((string) $request->query('to'));

        if ($token === '') {
            return response()->json(['data' => null]);
        }

        $rsvps = Rsvp::query()
            ->where('invitation_id', $publicInvitation->getKey())
            ->whereHas('guest', fn ($query) => $query->where('token', $token))
            ->latest('responded_at')
            ->get();

        return response()->json(['data' => RsvpResource::collection($rsvps)->resolve()]);
    }

    /**
     * A draft, expired or suspended invitation takes no answers — and says so
     * with a 404 rather than an explanation, because the renderer has already
     * decided what a guest at that URL sees.
     */
    private function assertOpen(Invitation $invitation): void
    {
        abort_unless($invitation->isLive(), 404);

        // The same resolved settings the renderer drew the form from, so a
        // package without RSVP cannot be posted to even by hand.
        abort_unless((InvitationSettings::for($invitation)['rsvp_enabled'] ?? false) === true, 404);
    }
}
