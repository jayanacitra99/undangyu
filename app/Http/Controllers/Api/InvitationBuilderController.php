<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\CheckPublishReadiness;
use App\Actions\Invitations\UpdateInvitationBasics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateInvitationRequest;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The builder's autosave endpoint (16.4).
 *
 * Every Vue island in Sessions 17–19 writes through here, so the contract is
 * fixed now: PATCH a partial payload, get the saved fields back on 200 or a
 * Laravel validation envelope on 422. Authorization is the same policy the
 * page itself was rendered under — the endpoint is not a side door.
 *
 * It runs on the session, not a token: this is the dashboard's own page
 * talking to its own backend, so the CSRF token applies and Sanctum is left
 * for the scanner app.
 */
final class InvitationBuilderController extends Controller
{
    public function update(
        UpdateInvitationRequest $request,
        Invitation $invitation,
        UpdateInvitationBasics $update,
        CheckPublishReadiness $readiness,
    ): JsonResponse {
        $invitation = $update($invitation, $request->validated());

        $requirements = $readiness($invitation);

        return response()->json([
            'data' => $invitation->only(UpdateInvitationBasics::FIELDS),
            'meta' => [
                'saved_at' => now()->toIso8601String(),
                // The islands draw the completeness indicator from this, so an
                // autosave that satisfies the last requirement updates it
                // without a page load.
                'ready_to_publish' => CheckPublishReadiness::isReady($requirements),
                'requirements' => array_map(
                    fn ($requirement): array => [
                        'key' => $requirement->key,
                        'label' => $requirement->label,
                        'met' => $requirement->met,
                        'hint' => $requirement->hint,
                        'tab' => $requirement->tab,
                    ],
                    $requirements,
                ),
            ],
        ]);
    }

    /**
     * Live slug availability for the "Dasar" tab (16.3).
     *
     * Deliberately not a validation endpoint: it answers one question about
     * one candidate and never writes. The same rules run again on save, so a
     * slug taken in the seconds between the two still loses there.
     */
    public function slugAvailability(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('update', $invitation);

        $validator = validator(
            $request->only('slug'),
            [
                'slug' => [
                    'required',
                    'string',
                    'min:3',
                    'max:120',
                    'regex:/^[a-z0-9]([a-z0-9\-]{1,118}[a-z0-9])$/',
                    Rule::unique('invitations', 'slug')->ignore($invitation->getKey()),
                ],
            ],
        );

        $slug = (string) $request->string('slug');

        $available = $validator->passes() && ! Invitation::slugIsBlocked($slug);

        return response()->json([
            'slug' => $slug,
            'available' => $available,
            'message' => $available
                ? __('Alamat tersedia.')
                : ($validator->errors()->first('slug') ?: __('Alamat ini sudah dipakai sistem. Pilih yang lain.')),
        ]);
    }
}
