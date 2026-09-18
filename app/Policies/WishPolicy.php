<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Delegates to the parent invitation — see InvitationChildPolicy.
 *
 * Moderating is `update` on the invitation, which is what it is: approving a
 * message is changing what the invitation shows.
 */
class WishPolicy extends InvitationChildPolicy {}
