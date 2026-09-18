<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Guests\ResolveGuestToken;
use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use App\Http\Controllers\Controller;
use App\Jobs\RecordGuestOpenJob;
use App\Jobs\RecordInvitationViewJob;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\Invitations\InvitationPayloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The published invitation at `undangyu.id/{slug}` (21.1, 21.2).
 *
 * The branches are docs/04 § 10, in its order:
 *
 *   draft      404 — an unpublished invitation is nobody's business, and
 *                    "this exists but you may not see it" leaks the slug.
 *                    The owner reaches it through the signed preview route.
 *   expired    a polite "this invitation has ended" page, never a 404: the
 *              link is in five hundred WhatsApp threads and will be clicked
 *              for years.
 *   suspended  a notice, for the same reason.
 *   published  the password gate if there is one, then the render.
 *
 * Nothing here writes: the view counter goes to the queue after the response
 * (hard rule 1).
 */
final class InvitationController extends Controller
{
    /**
     * How long one token's opens collapse into one. Half an hour covers a
     * guest reading the page, opening the map and coming back, without
     * hiding the fact that they returned the next day.
     */
    public const OPEN_WINDOW = 1800;

    public function __construct(private readonly InvitationPayloadService $payloads) {}

    public function show(Request $request, string $slug, ResolveGuestToken $resolveToken): View
    {
        // A reserved word cannot be an invitation, and answering anything but
        // 404 would tell a prober which names the system keeps for itself.
        abort_if(Invitation::slugIsBlocked($slug), 404);

        $payload = $this->payloads->forSlug($slug);

        abort_if($payload === null, 404);

        $status = InvitationStatus::from($payload['invitation']['status']);

        if ($status === InvitationStatus::Draft) {
            abort(404);
        }

        if ($status === InvitationStatus::Suspended) {
            return view('public.suspended', ['payload' => $payload]);
        }

        // `expires_at` in the past is expired whatever the column says: the
        // sweep that flips the status runs on a schedule, and a guest arriving
        // in the gap must not see a live invitation.
        if ($status === InvitationStatus::Expired || $this->hasExpired($payload)) {
            return view('public.ended', ['payload' => $payload]);
        }

        if ($this->needsUnlocking($request, $payload)) {
            return view('public.password', [
                'payload' => $payload,
                'slug' => $slug,
            ]);
        }

        // After the response, not during it. The job increments with the query
        // builder so the counter does not flush the payload cache.
        RecordInvitationViewJob::dispatch($payload['invitation']['uuid'])->afterResponse();

        // Who this link was sent to (27.1). Null for a shared link, a wrong
        // token or a deleted guest — all of which render the generic greeting,
        // because an error page here is a lost RSVP.
        $guest = $resolveToken($request->query('to'), $payload['invitation']['uuid']);

        if ($guest !== null) {
            $this->recordOpen($request, $guest);
        }

        return view('public.invitation', [
            'payload' => $payload,
            'isPreview' => false,
            'guest' => $guest === null ? null : [
                'name' => $guest->displayName(),
                'token' => $guest->token,
            ],
        ]);
    }

    /**
     * One open per guest per session (27.3, 27.5).
     *
     * A guest scrolling the page, reloading it or coming back from the RSVP
     * form is one open, not four. The session flag is what makes that true for
     * an ordinary visitor; the cache guard is what makes it true for the ones
     * whose browser refuses cookies, where every request would otherwise look
     * like a first visit.
     *
     * Both are checks, not writes to the invitation, and the job itself runs
     * after the response — hard rule 1 holds.
     */
    private function recordOpen(Request $request, Guest $guest): void
    {
        $sessionKey = 'invitation.opened.'.$guest->token;

        if ($request->session()->get($sessionKey, false) === true) {
            return;
        }

        $request->session()->put($sessionKey, true);

        // add() is atomic: the first caller for this token in the window wins,
        // everyone else is told the key already exists.
        if (! Cache::add('guest-open:'.$guest->token, true, self::OPEN_WINDOW)) {
            return;
        }

        RecordGuestOpenJob::dispatch($guest->getKey())->afterResponse();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasExpired(array $payload): bool
    {
        $expiresAt = $payload['invitation']['expires_at'] ?? null;

        return is_string($expiresAt) && now()->greaterThan($expiresAt);
    }

    /**
     * The unlock is per browser session and per invitation, so unlocking one
     * does not unlock the next.
     *
     * @param  array<string, mixed>  $payload
     */
    private function needsUnlocking(Request $request, array $payload): bool
    {
        if ($payload['invitation']['visibility'] !== InvitationVisibility::Password->value) {
            return false;
        }

        return ! $request->session()->get(self::unlockKey($payload['invitation']['uuid']), false);
    }

    public static function unlockKey(string $uuid): string
    {
        return "invitation.unlocked.{$uuid}";
    }
}
