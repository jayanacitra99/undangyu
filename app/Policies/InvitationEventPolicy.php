<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Delegates to the parent invitation — see InvitationChildPolicy.
 */
class InvitationEventPolicy extends InvitationChildPolicy {}
