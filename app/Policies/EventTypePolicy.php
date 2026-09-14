<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventType;
use App\Models\User;

/**
 * Catalog taxonomy is admin territory. It rides on `templates.manage` — the
 * "Manage templates/packages" row of the matrix in docs/04 § 11 — rather than a
 * permission of its own; super-admin passes through Gate::before.
 */
class EventTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function view(User $user, EventType $eventType): bool
    {
        return $user->can('templates.manage');
    }

    /**
     * Drag-and-drop sorting on the index — a collection-level write, so it has
     * no model instance to hang off.
     */
    public function reorder(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function update(User $user, EventType $eventType): bool
    {
        return $user->can('templates.manage');
    }

    public function delete(User $user, EventType $eventType): bool
    {
        return $user->can('templates.manage');
    }
}
