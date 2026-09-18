<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Wishes\SubmitWish;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreWishRequest;
use App\Http\Resources\WishResource;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Services\Wishes\WishFeed;
use App\Support\InvitationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The guestbook, from the invitation (29.2, 29.3).
 *
 * The feed is a cached read; the submission is a write the guest asked for and
 * is told about, on the same reasoning as the RSVP endpoint.
 */
final class WishController extends Controller
{
    public function index(Request $request, Invitation $publicInvitation, WishFeed $feed): JsonResponse
    {
        $this->assertOpen($publicInvitation);

        $page = max(1, (int) $request->integer('page', 1));

        return response()->json($feed->page($publicInvitation, $page));
    }

    public function store(
        StoreWishRequest $request,
        Invitation $publicInvitation,
        SubmitWish $submit,
    ): JsonResponse {
        $this->assertOpen($publicInvitation);

        // The honeypot was filled, so this is not a person. It gets the same
        // answer a person gets, and nothing is written: telling a bot it was
        // caught only teaches whoever wrote it to stop filling that field.
        if ($request->isBot()) {
            return response()->json(['data' => null, 'status' => 'pending'], Response::HTTP_CREATED);
        }

        $wish = $submit($publicInvitation, [
            ...$request->safe()->only(['name', 'message']),
            'ip_hash' => Rsvp::hashIp($request->ip()),
        ], $request->guest());

        return response()->json([
            // Null until it is public: a held message must not appear on the
            // page of the person who wrote it, or they will not understand why
            // nobody else can see it.
            'data' => $wish->status->isPublic() ? WishResource::make($wish)->resolve() : null,
            'status' => $wish->status->value,
        ], Response::HTTP_CREATED);
    }

    private function assertOpen(Invitation $invitation): void
    {
        abort_unless($invitation->isLive(), 404);
        abort_unless((InvitationSettings::for($invitation)['guestbook_enabled'] ?? false) === true, 404);
    }
}
