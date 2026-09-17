<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\OwnedByUserScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts a model's queries to the signed-in client (15.3).
 *
 * Defence in depth, not the gate: policies decide who may do what, and they
 * stay the thing to read when asking "who can edit this". This is what catches
 * the controller that forgets to scope its listing — a bug that leaks other
 * clients' rows rather than merely allowing an action.
 *
 * The model needs a `user_id`; `created_by` is honoured too where it exists,
 * for invitations a reseller built on someone else's behalf.
 */
trait ScopedToUser
{
    public static function bootScopedToUser(): void
    {
        static::addGlobalScope(new OwnedByUserScope);
    }

    /**
     * For the rare legitimate read across clients — an admin report, a queued
     * job resolving an order's invitation — where the caller has already
     * established that it is allowed.
     *
     * @return Builder<static>
     */
    public static function acrossAllUsers(): Builder
    {
        return static::query()->withoutGlobalScope(OwnedByUserScope::class);
    }
}
