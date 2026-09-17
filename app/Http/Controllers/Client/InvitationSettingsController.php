<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Invitations\UpdateInvitationSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateInvitationSettingsRequest;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;

/**
 * The "Pengaturan" tab (19.5).
 *
 * A form post, not an autosave: the password field is on this tab, and a
 * password that saves on keystroke would store every prefix the client typed.
 */
final class InvitationSettingsController extends Controller
{
    public function update(
        UpdateInvitationSettingsRequest $request,
        Invitation $invitation,
        UpdateInvitationSettings $update,
    ): RedirectResponse {
        $update($invitation, $request->validated());

        return redirect()
            ->route('client.invitations.edit', [$invitation, 'tab' => 'pengaturan'])
            ->with('status', __('Pengaturan tersimpan.'));
    }
}
