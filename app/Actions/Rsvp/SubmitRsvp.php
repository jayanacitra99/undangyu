<?php

declare(strict_types=1);

namespace App\Actions\Rsvp;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Notifications\NewRsvpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Records one attendance answer (M6.1, M6.3, M6.9).
 *
 * A named guest has exactly one answer per occasion: submitting again edits
 * what they said rather than adding a second row, which is what "edit your
 * response with the same link" means in practice and what stops a guest who
 * taps twice from being counted twice.
 *
 * An anonymous responder has no identity to update against, so each submission
 * is a new row. The rate limiter is what keeps that honest.
 */
final class SubmitRsvp
{
    /**
     * How long one invitation's RSVP notifications are collapsed for (28.5).
     * Four hundred individual emails on the day the links go out is hostile;
     * one every fifteen minutes says the same thing.
     */
    public const NOTIFY_WINDOW = 900;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?Guest $guest = null): Rsvp
    {
        $eventId = $attributes['invitation_event_id'] ?? null;

        $rsvp = DB::transaction(function () use ($invitation, $attributes, $guest, $eventId): Rsvp {
            $existing = $guest === null
                ? null
                : $invitation->rsvps()
                    ->where('guest_id', $guest->getKey())
                    ->where('invitation_event_id', $eventId)
                    ->first();

            $payload = [
                ...$attributes,
                'guest_id' => $guest?->getKey(),
                'responded_at' => now(),
            ];

            // The token is how the guest was recognised; it is not a column.
            unset($payload['token']);

            if ($existing !== null) {
                $existing->fill($payload)->save();

                return $existing->refresh();
            }

            return $invitation->rsvps()->create($payload);
        });

        $this->notify($invitation, $rsvp);

        return $rsvp;
    }

    /**
     * One notification per invitation per window. Cache::add is the claim: the
     * first responder in the window sends the mail, everyone after them is
     * counted on the dashboard and nowhere else.
     */
    private function notify(Invitation $invitation, Rsvp $rsvp): void
    {
        if (! Cache::add('rsvp-notify:'.$invitation->getKey(), true, self::NOTIFY_WINDOW)) {
            return;
        }

        $invitation->loadMissing('user');
        $invitation->user?->notify(new NewRsvpNotification($invitation, $rsvp));
    }
}
