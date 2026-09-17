<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Writes a new order onto one invitation's children (M4.3, M4.4, M4.11).
 *
 * The ids are filtered against the relation before anything is written, so an
 * id belonging to another invitation is dropped rather than adopted — the
 * policy already refused the caller, and this makes a crafted payload
 * pointless as well as unauthorized.
 *
 * The island always sends the whole list, so omitted ids are not a case to
 * design for: anything it leaves out simply keeps the sort_order it had.
 */
final class ReorderInvitationChildren
{
    /**
     * @param  HasMany<covariant Model, Invitation>  $relation
     * @param  list<int>  $ids
     * @return int how many rows were moved
     */
    public function __invoke(HasMany $relation, array $ids): int
    {
        /** @var list<int> $owned */
        $owned = $relation->pluck('id')->map('intval')->all();

        $ordered = array_values(array_filter($ids, fn (int $id): bool => in_array($id, $owned, true)));

        if ($ordered === []) {
            return 0;
        }

        return DB::transaction(function () use ($relation, $ordered): int {
            $moved = 0;

            foreach ($ordered as $position => $id) {
                $moved += $relation->getRelated()->newQuery()
                    ->whereKey($id)
                    ->update(['sort_order' => $position]);
            }

            return $moved;
        });
    }
}
