<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\Invitation;
use App\Support\PhoneNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one guest (M5.1).
 *
 * The token is generated once, on creation, and never again: it is already in
 * a WhatsApp message by the time anyone renames a guest, and regenerating it
 * would break a link that is out in the world. A typo in a name is a cheap
 * thing to fix; a dead invitation link is not.
 */
final class SaveGuest
{
    /**
     * Two imports of the same list running at once can both be told a token is
     * free before either has written it. The unique index is what actually
     * decides, so a violation is caught and retried rather than surfaced — the
     * generator picks a new suffix and the second writer wins on its own token.
     */
    public const TOKEN_RETRIES = 5;

    public function __construct(private readonly GenerateGuestToken $generateToken) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?Guest $guest = null): Guest
    {
        if (array_key_exists('phone', $attributes)) {
            $attributes['phone'] = PhoneNumber::normalize($attributes['phone']);
        }

        if ($guest !== null) {
            return DB::transaction(function () use ($attributes, $guest): Guest {
                $guest->fill($attributes)->save();

                return $guest->refresh();
            });
        }

        return $this->create($invitation, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function create(Invitation $invitation, array $attributes): Guest
    {
        $name = (string) ($attributes['name'] ?? '');

        for ($attempt = 1; ; $attempt++) {
            $attributes['token'] = ($this->generateToken)($name, $this->taken(...));

            try {
                return $invitation->guests()->create($attributes);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::TOKEN_RETRIES) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * `withTrashed`, because a soft-deleted guest still owns their token: the
     * link may be in someone's chat history, and restoring the guest must not
     * find their token now pointing at a different person.
     */
    private function taken(string $token): bool
    {
        return Guest::withTrashed()->where('token', $token)->exists();
    }
}
