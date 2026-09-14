<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

/**
 * The `packages.manage` half of the "Manage templates/packages" matrix row
 * (docs/04 § 11).
 */
class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('packages.manage');
    }

    public function view(User $user, Package $package): bool
    {
        return $user->can('packages.manage');
    }

    /**
     * Drag-and-drop sorting on the index — a collection-level write, so it has
     * no model instance to hang off.
     */
    public function reorder(User $user): bool
    {
        return $user->can('packages.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('packages.manage');
    }

    public function update(User $user, Package $package): bool
    {
        return $user->can('packages.manage');
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->can('packages.manage');
    }
}
