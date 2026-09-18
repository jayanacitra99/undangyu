<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use App\Http\Controllers\Controller;
use App\Jobs\RecordInvitationViewJob;
use App\Models\Invitation;
use App\Services\Invitations\InvitationPayloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
    public function __construct(private readonly InvitationPayloadService $payloads) {}

    public function show(Request $request, string $slug): View
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

        return view('public.invitation', [
            'payload' => $payload,
            'isPreview' => false,
        ]);
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
