<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\ReorderInvitationChildren;
use App\Actions\Invitations\SaveInvitationSection;
use App\Enums\SectionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReorderInvitationChildrenRequest;
use App\Http\Requests\Client\StoreCustomSectionRequest;
use App\Http\Requests\Client\UpdateInvitationSectionRequest;
use App\Http\Resources\InvitationSectionResource;
use App\Models\Invitation;
use App\Models\InvitationSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Section order, visibility and custom blocks (19.3).
 *
 * Only a custom section can be created or deleted here. The built-in ones are
 * what the template renders — a client hiding the gallery is switching it off,
 * not removing a row the renderer still expects to find.
 */
final class InvitationSectionController extends Controller
{
    public function store(
        StoreCustomSectionRequest $request,
        Invitation $invitation,
        SaveInvitationSection $save,
    ): JsonResponse {
        $section = $save($invitation, $request->attributesForSection());

        return InvitationSectionResource::make($section)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateInvitationSectionRequest $request,
        InvitationSection $section,
        SaveInvitationSection $save,
    ): InvitationSectionResource {
        $section->loadMissing('invitation');

        return InvitationSectionResource::make(
            $save($section->invitation, $request->attributesForSection(), $section)
        );
    }

    public function destroy(InvitationSection $section): Response
    {
        Gate::authorize('delete', $section);

        abort_if(
            $section->section_key !== SectionKey::Custom,
            422,
            __('Bagian bawaan tidak bisa dihapus. Sembunyikan saja.'),
        );

        $section->delete();

        return response()->noContent();
    }

    public function reorder(
        ReorderInvitationChildrenRequest $request,
        Invitation $invitation,
        ReorderInvitationChildren $reorder,
    ): JsonResponse {
        return response()->json(['moved' => $reorder($invitation->sections(), $request->ids())]);
    }
}
