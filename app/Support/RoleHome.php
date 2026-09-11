<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Where a user lands after authenticating.
 *
 * The three surfaces from docs/05 § 2 each have their own route file and role
 * gate, so "the home page" depends entirely on who you are. Keeping that in one
 * place means login, registration and any future impersonation all agree.
 */
final class RoleHome
{
    public const ADMIN = '/admin';

    public const CLIENT = '/dashboard';

    public const USHER = '/scanner';

    /**
     * @var list<string>
     */
    public const ADMIN_ROLES = ['super-admin', 'admin', 'support'];

    /**
     * @var list<string>
     */
    public const CLIENT_ROLES = ['client', 'reseller'];

    public static function for(?User $user): string
    {
        return match (true) {
            $user === null => '/login',
            $user->hasAnyRole(self::ADMIN_ROLES) => self::ADMIN,
            $user->hasAnyRole(self::CLIENT_ROLES) => self::CLIENT,
            $user->hasRole('usher') => self::USHER,
            default => '/',
        };
    }
}
