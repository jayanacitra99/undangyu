<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Invitations\PublishInvitation;
use App\Actions\Invitations\UnpublishInvitation;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Publishing and unpublishing from the builder (23.2, 23.5).
 *
 * The button is disabled until every gate item passes, but the action checks
 * again: a client can leave the builder open while a photo is deleted in
 * another tab, and "the button was enabled" is not a fact about the database.
 */
final class InvitationPublishController extends Controller
{
    public function store(Invitation $invitation, PublishInvitation $publish): RedirectResponse
    {
        Gate::authorize('publish', $invitation);

        $result = $publish($invitation);

        if (! $result['published']) {
            $missing = collect($result['requirements'])
                ->reject(fn ($requirement): bool => $requirement->met)
                ->map(fn ($requirement): string => $requirement->label)
                ->join(', ');

            return back()->with('error', __('Belum bisa terbit. Lengkapi dulu: :items.', ['items' => $missing]));
        }

        return redirect()
            ->route('client.invitations.edit', $invitation)
            ->with('status', __('Undangan sudah terbit.'));
    }

    public function destroy(Invitation $invitation, UnpublishInvitation $unpublish): RedirectResponse
    {
        // `publish` covers both directions: taking an invitation down is the
        // same authority as putting it up, and neither belongs to a suspended
        // invitation's owner.
        Gate::authorize('publish', $invitation);

        $unpublish($invitation);

        return redirect()
            ->route('client.invitations.edit', $invitation)
            ->with('status', __('Undangan kembali menjadi draf dan tidak bisa dibuka tamu.'));
    }
}
