<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Contracts\BelongsToInvitation;
use App\Models\Invitation;
use App\Support\InvitationCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Flushes an invitation's cached payload whenever anything in its tree changes
 * (20.3, hard rule 5, docs/05 § 7).
 *
 * Registered on the invitation and on **every** child model. Missing one is
 * not a performance bug: the client edits their venue, the page keeps serving
 * the old address, and nobody finds out until a guest drives to the wrong
 * building. Which is why this class takes any model rather than a specific
 * one — adding a child model means adding the attribute, not writing a new
 * observer and remembering to flush.
 *
 * `saved` covers both creates and updates; `deleted` and `restored` cover the
 * rest. A soft delete arrives as `deleted` too.
 */
class InvalidatesInvitationCache
{
    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    public function restored(Model $model): void
    {
        $this->flush($model);
    }

    /**
     * The invitation this write belongs to: the row itself, or the child's
     * parent. A child whose `invitation_id` is somehow absent is skipped
     * rather than flushing something arbitrary.
     */
    private function flush(Model $model): void
    {
        if ($model instanceof Invitation) {
            InvitationCache::flush($model->getKey());

            return;
        }

        if ($model instanceof BelongsToInvitation) {
            InvitationCache::flush($model->invitationId());
        }
    }
}
