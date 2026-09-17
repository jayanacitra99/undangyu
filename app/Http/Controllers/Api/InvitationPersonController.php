<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\ReorderInvitationChildren;
use App\Actions\Invitations\SaveInvitationPerson;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReorderInvitationChildrenRequest;
use App\Http\Requests\Client\StoreInvitationPersonRequest;
use App\Http\Requests\Client\UpdateInvitationPersonRequest;
use App\Http\Requests\Client\UploadPersonPhotoRequest;
use App\Http\Resources\InvitationPersonResource;
use App\Models\Invitation;
use App\Models\InvitationPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Person CRUD behind the PersonsEditor island (17.2).
 *
 * Authorization is the FormRequest's, which asks InvitationPersonPolicy, which
 * asks InvitationPolicy about the parent. A person of another client's
 * invitation is a 403 here rather than a 404: unlike the invitation itself,
 * these routes carry no tenant-scoped binding, and pretending the row does not
 * exist would be a different lie than the one the policy is telling.
 */
final class InvitationPersonController extends Controller
{
    public function store(
        StoreInvitationPersonRequest $request,
        Invitation $invitation,
        SaveInvitationPerson $save,
    ): JsonResponse {
        $person = $save($invitation, $request->validated());

        return InvitationPersonResource::make($person)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateInvitationPersonRequest $request,
        InvitationPerson $person,
        SaveInvitationPerson $save,
    ): InvitationPersonResource {
        $person->loadMissing('invitation');

        return InvitationPersonResource::make(
            $save($person->invitation, $request->validated(), $person)
        );
    }

    public function destroy(InvitationPerson $person): Response
    {
        Gate::authorize('delete', $person);

        $photo = $person->photo;

        $person->delete();

        if ($photo !== null) {
            Storage::disk(InvitationPerson::PHOTO_DISK)->delete($photo);
        }

        return response()->noContent();
    }

    public function reorder(
        ReorderInvitationChildrenRequest $request,
        Invitation $invitation,
        ReorderInvitationChildren $reorder,
    ): JsonResponse {
        $moved = $reorder($invitation->persons(), $request->ids());

        return response()->json(['moved' => $moved]);
    }

    /**
     * A replacement photo is written before the old one is deleted, so a
     * failed upload never leaves the card pointing at nothing.
     */
    public function photo(UploadPersonPhotoRequest $request, InvitationPerson $person): InvitationPersonResource
    {
        $previous = $person->photo;

        $path = $request->file('photo')->store(
            InvitationPerson::PHOTO_DIRECTORY,
            InvitationPerson::PHOTO_DISK,
        );

        $person->update(['photo' => $path]);

        if ($previous !== null && $previous !== $path) {
            Storage::disk(InvitationPerson::PHOTO_DISK)->delete($previous);
        }

        $person->loadMissing('invitation');

        return InvitationPersonResource::make($person);
    }

    public function deletePhoto(InvitationPerson $person): InvitationPersonResource
    {
        Gate::authorize('update', $person);

        $photo = $person->photo;

        $person->update(['photo' => null]);

        if ($photo !== null) {
            Storage::disk(InvitationPerson::PHOTO_DISK)->delete($photo);
        }

        return InvitationPersonResource::make($person);
    }
}
