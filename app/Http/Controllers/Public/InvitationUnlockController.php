<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\InvitationVisibility;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * The passphrase gate on a password-protected invitation (21.4).
 *
 * The unlock lives in the guest's session, scoped to one invitation's uuid: a
 * guest who was given the passphrase for one wedding does not thereby open
 * another. The route is rate limited, because a four-character passphrase is
 * what most clients will choose.
 *
 * This is the only public endpoint that reads `invitations.password`, and it
 * only ever compares — the hash never leaves the request.
 */
final class InvitationUnlockController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'max:100'],
        ]);

        $invitation = Invitation::acrossAllUsers()
            ->where('slug', $slug)
            ->first();

        abort_if($invitation === null, 404);

        // Nothing to unlock, and saying so would confirm the slug exists to
        // someone poking at invitations that are not gated.
        abort_unless($invitation->visibility === InvitationVisibility::Password, 404);

        if ($invitation->password === null || ! Hash::check((string) $request->string('password'), $invitation->password)) {
            throw ValidationException::withMessages([
                'password' => __('Kata sandi salah.'),
            ]);
        }

        $request->session()->put(InvitationController::unlockKey($invitation->uuid), true);

        return redirect()->route('invitation.show', ['slug' => $slug]);
    }
}
