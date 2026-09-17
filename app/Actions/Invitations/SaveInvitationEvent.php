<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Support\MapsLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one event session (M4.4).
 *
 * Two things happen here that the FormRequest deliberately does not do.
 *
 * Times arrive as wall-clock in the invitation's timezone — 19:00 means 19:00
 * where the wedding is — and are converted to UTC for storage, per CLAUDE.md.
 *
 * A pasted Maps link is read for coordinates (17.4), but never over
 * coordinates the client typed themselves: the parse is a convenience, and the
 * client's own numbers outrank it.
 */
final class SaveInvitationEvent
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?InvitationEvent $event = null): InvitationEvent
    {
        $attributes = $this->toUtc($attributes, $invitation->timezone);
        $attributes = $this->withCoordinates($attributes);

        return DB::transaction(function () use ($invitation, $attributes, $event): InvitationEvent {
            if ($event === null) {
                $attributes['sort_order'] = ((int) ($invitation->events()->max('sort_order') ?? -1)) + 1;

                return $invitation->events()->create($attributes);
            }

            $event->fill($attributes);
            $event->save();

            return $event->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function toUtc(array $attributes, string $timezone): array
    {
        foreach (['start_at', 'end_at'] as $field) {
            if (! array_key_exists($field, $attributes) || $attributes[$field] === null) {
                continue;
            }

            $attributes[$field] = Carbon::parse((string) $attributes[$field], $timezone)->utc();
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withCoordinates(array $attributes): array
    {
        if (! array_key_exists('maps_url', $attributes)) {
            return $attributes;
        }

        $typedLatitude = $attributes['latitude'] ?? null;
        $typedLongitude = $attributes['longitude'] ?? null;

        if ($typedLatitude !== null && $typedLongitude !== null) {
            return $attributes;
        }

        $parsed = MapsLink::coordinates(is_string($attributes['maps_url']) ? $attributes['maps_url'] : null);

        if ($parsed === null) {
            // A short link carries no coordinates until it is followed, and
            // this does not follow links. Whatever the row already had stands.
            return $attributes;
        }

        $attributes['latitude'] = $typedLatitude ?? $parsed['latitude'];
        $attributes['longitude'] = $typedLongitude ?? $parsed['longitude'];

        return $attributes;
    }
}
