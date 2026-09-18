<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;

/**
 * Turns `?to={token}` into the guest it belongs to (27.1).
 *
 * Never throws and never 404s. A token can be wrong for a dozen ordinary
 * reasons — a truncated WhatsApp link, a guest deleted after the message went
 * out, a link forwarded to a friend — and every one of them ends with somebody
 * who wants to come to the wedding looking at the page. An error there is a
 * lost RSVP, so an unresolvable token is simply nobody.
 *
 * The lookup is bound to the invitation being rendered: a token is unique
 * across the table, but resolving one against the wrong invitation would print
 * a stranger's name on someone else's cover.
 */
final class ResolveGuestToken
{
    /**
     * The `?to=` value is a slug plus a suffix, so anything else is not worth
     * a query. The cap matches the column.
     */
    public const PATTERN = '/^[a-z0-9-]{1,80}$/';

    public function __invoke(?string $token, string $invitationUuid): ?Guest
    {
        $token = trim((string) $token);

        if ($token === '' || preg_match(self::PATTERN, $token) !== 1) {
            return null;
        }

        return Guest::query()
            ->where('token', $token)
            ->whereIn('invitation_id', function ($query) use ($invitationUuid): void {
                $query->select('id')->from('invitations')->where('uuid', $invitationUuid);
            })
            ->first();
    }
}
