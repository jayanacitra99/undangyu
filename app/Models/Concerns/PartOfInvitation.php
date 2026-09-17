<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * The `invitationId()` half of App\Models\Contracts\BelongsToInvitation.
 *
 * One line, six models, and it keeps the policy from reaching for an
 * undeclared magic property.
 */
trait PartOfInvitation
{
    public function invitationId(): int
    {
        return (int) $this->getAttribute('invitation_id');
    }
}
