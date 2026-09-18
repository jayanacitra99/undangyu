<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Delegates to the parent invitation — see InvitationChildPolicy.
 *
 * Guests never reach a policy: they submit through the public endpoint, which
 * is bound to the invitation in the URL and rate limited, and they can only
 * ever see their own answer back through their own token.
 */
class RsvpPolicy extends InvitationChildPolicy {}
