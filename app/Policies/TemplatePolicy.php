<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

/**
 * Same gate as the rest of the catalog — see EventTypePolicy.
 */
class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function view(User $user, Template $template): bool
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

    public function update(User $user, Template $template): bool
    {
        return $user->can('templates.manage');
    }

    public function delete(User $user, Template $template): bool
    {
        return $user->can('templates.manage');
    }
}
