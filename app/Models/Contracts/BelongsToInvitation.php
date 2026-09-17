<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A model that is part of one invitation.
 *
 * InvitationChildPolicy authorizes through the parent, so it needs to know the
 * relation exists rather than hoping every model it is handed happens to have
 * one. App\Models\Concerns\PartOfInvitation satisfies `invitationId()`.
 */
interface BelongsToInvitation
{
    public function invitation(): BelongsTo;

    /**
     * The parent's key, without loading the parent.
     */
    public function invitationId(): int;
}
