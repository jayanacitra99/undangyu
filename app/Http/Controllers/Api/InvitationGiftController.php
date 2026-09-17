<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\ReorderInvitationChildren;
use App\Actions\Invitations\SaveInvitationGift;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReorderInvitationChildrenRequest;
use App\Http\Requests\Client\StoreInvitationGiftRequest;
use App\Http\Requests\Client\UpdateInvitationGiftRequest;
use App\Http\Requests\Client\UploadChildImageRequest;
use App\Http\Resources\InvitationGiftResource;
use App\Models\Invitation;
use App\Models\InvitationGift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Gift destinations behind the GiftsEditor island (19.1).
 */
final class InvitationGiftController extends Controller
{
    public function store(
        StoreInvitationGiftRequest $request,
        Invitation $invitation,
        SaveInvitationGift $save,
    ): JsonResponse {
        return InvitationGiftResource::make($save($invitation, $request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateInvitationGiftRequest $request,
        InvitationGift $gift,
        SaveInvitationGift $save,
    ): InvitationGiftResource {
        $gift->loadMissing('invitation');

        return InvitationGiftResource::make($save($gift->invitation, $request->validated(), $gift));
    }

    public function destroy(InvitationGift $gift): Response
    {
        Gate::authorize('delete', $gift);

        $image = $gift->qris_image;

        $gift->delete();

        if ($image !== null) {
            Storage::disk(InvitationGift::IMAGE_DISK)->delete($image);
        }

        return response()->noContent();
    }

    public function reorder(
        ReorderInvitationChildrenRequest $request,
        Invitation $invitation,
        ReorderInvitationChildren $reorder,
    ): JsonResponse {
        return response()->json(['moved' => $reorder($invitation->gifts(), $request->ids())]);
    }

    /**
     * The QRIS code itself. Written before the old one is removed, so a failed
     * upload never leaves the card pointing at nothing.
     */
    public function image(UploadChildImageRequest $request, InvitationGift $gift): InvitationGiftResource
    {
        $previous = $gift->qris_image;

        $path = $request->file('image')->store(
            InvitationGift::IMAGE_DIRECTORY,
            InvitationGift::IMAGE_DISK,
        );

        $gift->update(['qris_image' => $path]);

        if ($previous !== null && $previous !== $path) {
            Storage::disk(InvitationGift::IMAGE_DISK)->delete($previous);
        }

        return InvitationGiftResource::make($gift->refresh());
    }
}
