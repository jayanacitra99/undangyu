<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TemplateCategory;
use App\Models\User;

/**
 * Same gate as the event types — see EventTypePolicy.
 */
class TemplateCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function view(User $user, TemplateCategory $templateCategory): bool
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

    public function update(User $user, TemplateCategory $templateCategory): bool
    {
        return $user->can('templates.manage');
    }

    public function delete(User $user, TemplateCategory $templateCategory): bool
    {
        return $user->can('templates.manage');
    }
}
