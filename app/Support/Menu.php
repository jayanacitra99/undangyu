<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Turns a config/menu.php array into the items one user may actually see.
 *
 * The sidebar Blade renders whatever this returns and decides nothing itself.
 * Two things drop an item: the user lacks its permission, or its route is not
 * registered yet — later sessions add the routes and the item appears then,
 * with no edit here.
 */
final class Menu
{
    /**
     * Which menu a user gets on a page that serves both surfaces, like /profile.
     */
    public static function keyFor(?User $user): string
    {
        return $user?->hasAnyRole(RoleHome::ADMIN_ROLES) ? 'admin' : 'client';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function for(string $key, ?User $user): array
    {
        if ($user === null) {
            return [];
        }

        /** @var list<array<string, mixed>> $items */
        $items = config("menu.{$key}", []);

        return self::filter($items, $user);
    }

    /**
     * Is the item — or, for a parent, any of its children — the current page?
     *
     * @param  array<string, mixed>  $item
     */
    public static function isActive(array $item): bool
    {
        /** @var list<string> $patterns */
        $patterns = $item['active'] ?? [];

        if ($patterns !== [] && request()->is(...$patterns)) {
            return true;
        }

        /** @var list<array<string, mixed>> $children */
        $children = $item['children'] ?? [];

        foreach ($children as $child) {
            if (self::isActive($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private static function filter(array $items, User $user): array
    {
        $visible = [];

        foreach ($items as $item) {
            /** @var list<array<string, mixed>> $children */
            $children = $item['children'] ?? [];

            if ($children !== []) {
                $children = self::filter($children, $user);

                // A parent with nothing left under it is not a menu item.
                if ($children === []) {
                    continue;
                }

                $item['children'] = $children;
                $visible[] = $item;

                continue;
            }

            if (self::allows($item, $user)) {
                $visible[] = $item;
            }
        }

        return $visible;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function allows(array $item, User $user): bool
    {
        /** @var string|null $route */
        $route = $item['route'] ?? null;

        if ($route === null || ! Route::has($route)) {
            return false;
        }

        /** @var string|null $permission */
        $permission = $item['permission'] ?? null;

        return $permission === null || $user->can($permission);
    }
}
