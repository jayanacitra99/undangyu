<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limits a query to rows the signed-in client owns (15.3).
 *
 * Applies only to an authenticated non-staff user: a queue worker, a console
 * command and the admin panel all run unscoped, which is why the policies are
 * still the gate and this is only the net beneath them.
 *
 * The model must carry `user_id`, and may carry `created_by` for the reseller
 * case — see App\Models\Concerns\ScopedToUser.
 */
final class OwnedByUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user instanceof User || $user->isStaff()) {
            return;
        }

        $builder->where(function (Builder $query) use ($model, $user): void {
            $query->where($model->qualifyColumn('user_id'), $user->getKey());

            if ($this->hasCreatedBy($model)) {
                $query->orWhere($model->qualifyColumn('created_by'), $user->getKey());
            }
        });
    }

    /**
     * A reseller builds invitations owned by someone else; most models have no
     * such column.
     */
    private function hasCreatedBy(Model $model): bool
    {
        return in_array('created_by', $model->getFillable(), true);
    }
}
