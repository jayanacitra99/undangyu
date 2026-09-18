<?php

declare(strict_types=1);

namespace App\Services\Guests;

use App\Enums\FeatureKey;
use App\Models\Invitation;

/**
 * How many guests an invitation has room for (24.3, hard rule 10).
 *
 * Counted against `invitations.entitlements` — the snapshot taken at
 * provisioning — never against what the package grants today.
 *
 * Soft-deleted guests do not count. A client who deletes ten guests to make
 * room has made room; keeping the row for its RSVP is our bookkeeping, not
 * something they should pay for.
 */
final class GuestQuota
{
    public function used(Invitation $invitation): int
    {
        return $invitation->guests()->count();
    }

    /**
     * The guest limit, or null for unlimited.
     */
    public function limit(Invitation $invitation): ?int
    {
        $value = $invitation->entitlement(FeatureKey::MaxGuests);

        return $value === null ? null : (int) $value;
    }

    /**
     * Is there room for this many more guests? `$incoming` is what makes the
     * same check serve the import of Session 25.
     */
    public function hasRoom(Invitation $invitation, int $incoming = 1): bool
    {
        return $this->remaining($invitation) === null
            || $this->remaining($invitation) >= $incoming;
    }

    /**
     * How many more fit, or null for unlimited. Never negative: an invitation
     * whose package was downgraded is full, not owed guests.
     */
    public function remaining(Invitation $invitation): ?int
    {
        $limit = $this->limit($invitation);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->used($invitation));
    }

    /**
     * What the guest page prints above the table, and what an over-quota
     * response says.
     *
     * @return array{used: int, limit: int|null, remaining: int|null}
     */
    public function summary(Invitation $invitation): array
    {
        return [
            'used' => $this->used($invitation),
            'limit' => $this->limit($invitation),
            'remaining' => $this->remaining($invitation),
        ];
    }
}
