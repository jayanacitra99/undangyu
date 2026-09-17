<?php

declare(strict_types=1);

namespace App\Services\Invitations;

use App\Http\Resources\InvitationPayload;
use App\Models\Invitation;
use App\Support\InvitationCache;

/**
 * The public payload of one invitation, built once and cached (20.2).
 *
 * A published invitation is read far more often than it is written — a couple
 * edits it for a week and then five hundred guests open it in one evening. So
 * the whole tree is loaded in one eager-loaded query, resolved into a plain
 * array, and kept under the `invitation:{id}` tag until something in that tree
 * changes.
 */
final class InvitationPayloadService
{
    /**
     * The eager loads that make a cold build one query per relation rather
     * than one per row. Ordering is the relations' own (`sort_order`), and
     * events carry `chaperone()` so each one can read its invitation's
     * timezone without a lazy load.
     *
     * @var list<string>
     */
    public const RELATIONS = [
        'eventType',
        'template',
        'sections',
        'persons',
        'events',
        'stories',
        'media',
        'gifts',
    ];

    /**
     * Bumped whenever InvitationPayload's shape changes.
     *
     * A deploy that adds a key to the payload leaves an hour of caches built
     * by the old code, and the new renderer reads them — which is a 500 on
     * every published invitation until they expire. The version is part of the
     * key, so old entries are simply never asked for again.
     */
    public const SHAPE_VERSION = 2;

    public static function key(string $slug): string
    {
        return 'invitation:payload:v'.self::SHAPE_VERSION.":{$slug}";
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forSlug(string $slug): ?array
    {
        $id = $this->idFor($slug);

        if ($id === null) {
            return null;
        }

        // The tree is loaded inside the closure, not before it: a warm read is
        // the id lookup and a cache hit, and eager loading eight relations to
        // then throw them away would make the cache cost more than it saves.
        return InvitationCache::remember(
            $id,
            self::key($slug),
            fn (): array => $this->build($this->load($id)),
        );
    }

    /**
     * For a caller that already holds the model — the builder's preview, the
     * publish action warming the cache.
     *
     * @return array<string, mixed>
     */
    public function forInvitation(Invitation $invitation): array
    {
        return InvitationCache::remember(
            $invitation->getKey(),
            self::key($invitation->slug),
            fn (): array => $this->build($invitation->loadMissing(self::RELATIONS)),
        );
    }

    public function forget(Invitation $invitation): void
    {
        InvitationCache::forget($invitation->getKey(), self::key($invitation->slug));
    }

    /**
     * Resolving the slug is its own small indexed query, because the cache tag
     * is built from the id and there is no id until something has looked it
     * up. One column, on a unique index.
     *
     * `acrossAllUsers`, because this is the public renderer's path: a signed-in
     * client looking at someone else's published invitation must see it, and
     * the tenant scope would hide it.
     */
    private function idFor(string $slug): ?int
    {
        $id = Invitation::acrossAllUsers()
            ->where('slug', $slug)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * The whole tree in one query per relation. Only ever runs on a cold
     * cache.
     */
    private function load(int $id): Invitation
    {
        return Invitation::acrossAllUsers()
            ->with(self::RELATIONS)
            ->whereKey($id)
            ->sole();
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Invitation $invitation): array
    {
        return InvitationPayload::make($invitation)->resolve();
    }
}
