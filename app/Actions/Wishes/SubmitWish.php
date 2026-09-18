<?php

declare(strict_types=1);

namespace App\Actions\Wishes;

use App\Enums\WishStatus;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Wish;
use App\Services\Wishes\ProfanityFilter;
use App\Services\Wishes\WishFeed;
use App\Support\InvitationSettings;

/**
 * Records one guestbook message (M6.4, M6.5).
 *
 * Which status it is born with is the invitation's own decision: `auto` puts
 * it straight on the page, `manual` holds it for the client. A word from the
 * blocklist holds it either way — the couple would rather decide that one
 * themselves than find it under their photos.
 */
final class SubmitWish
{
    public function __construct(
        private readonly ProfanityFilter $profanity,
        private readonly WishFeed $feed,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?Guest $guest = null): Wish
    {
        $message = (string) ($attributes['message'] ?? '');

        $wish = $invitation->wishes()->create([
            'name' => $attributes['name'],
            'message' => $message,
            'guest_id' => $guest?->getKey(),
            'ip_hash' => $attributes['ip_hash'] ?? null,
            'status' => $this->status($invitation, $message),
        ]);

        // The feed is cached for a minute (29.3), so a wish that is public the
        // moment it is written has to clear that minute — otherwise the guest
        // who just wrote it reloads and does not see it.
        if ($wish->status->isPublic()) {
            $this->feed->forget($invitation);
        }

        return $wish;
    }

    private function status(Invitation $invitation, string $message): WishStatus
    {
        if (! $this->profanity->isClean($message)) {
            return WishStatus::Pending;
        }

        $mode = InvitationSettings::for($invitation)['guestbook_moderation'] ?? 'auto';

        return $mode === 'manual' ? WishStatus::Pending : WishStatus::Approved;
    }
}
