<?php

declare(strict_types=1);

namespace App\Services\Wishes;

use App\Http\Resources\WishResource;
use App\Models\Invitation;
use App\Models\Wish;
use App\Support\InvitationCache;

/**
 * The public guestbook feed (29.3).
 *
 * Paginated, pinned first, newest after that — and the first page is cached
 * for a minute, because that is the page five hundred guests load within the
 * same hour and none of them needs it to be a second fresh.
 *
 * Only the first page is cached. Deeper pages are read by the handful of
 * people who scroll that far, and caching them would mean tracking keys that
 * a single new wish has to invalidate.
 */
final class WishFeed
{
    public const TTL = 60;

    public const PER_PAGE = 10;

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}}
     */
    public function page(Invitation $invitation, int $page = 1): array
    {
        if ($page > 1) {
            return $this->read($invitation, $page);
        }

        /** @var array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}} $cached */
        $cached = InvitationCache::remember(
            $invitation->getKey(),
            self::key($invitation),
            fn (): array => $this->read($invitation, 1),
            self::TTL,
        );

        return $cached;
    }

    /**
     * Drops the cached first page. Called when a wish is published — by a
     * guest whose invitation publishes immediately, or by a client approving
     * one — because a guest who writes a message and cannot see it assumes it
     * was lost and writes it again.
     */
    public function forget(Invitation $invitation): void
    {
        InvitationCache::forget($invitation->getKey(), self::key($invitation));
    }

    public static function key(Invitation $invitation): string
    {
        return 'wishes:feed:'.$invitation->getKey();
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}}
     */
    private function read(Invitation $invitation, int $page): array
    {
        $wishes = Wish::query()
            ->where('invitation_id', $invitation->getKey())
            ->public()
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        return [
            'data' => WishResource::collection($wishes->items())->resolve(),
            'meta' => [
                'current_page' => $wishes->currentPage(),
                'last_page' => $wishes->lastPage(),
                'total' => $wishes->total(),
            ],
        ];
    }
}
