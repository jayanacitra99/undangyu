<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Invitations\UpdateThemeConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateThemeConfigRequest;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;

/**
 * The "Tema" tab (19.4).
 *
 * An ordinary form post rather than an autosave island: the controls are
 * generated from the template's schema, and a colour picker that saves on
 * every drag would write a row per pixel of the gradient.
 */
final class InvitationThemeController extends Controller
{
    public function update(
        UpdateThemeConfigRequest $request,
        Invitation $invitation,
        UpdateThemeConfig $update,
    ): RedirectResponse {
        $update($invitation, $request->themeConfig());

        return redirect()
            ->route('client.invitations.edit', [$invitation, 'tab' => 'tema'])
            ->with('status', __('Tema tersimpan.'));
    }
}
