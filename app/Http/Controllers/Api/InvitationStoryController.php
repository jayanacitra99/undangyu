<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\ReorderInvitationChildren;
use App\Actions\Invitations\SaveInvitationStory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReorderInvitationChildrenRequest;
use App\Http\Requests\Client\StoreInvitationStoryRequest;
use App\Http\Requests\Client\UpdateInvitationStoryRequest;
use App\Http\Requests\Client\UploadChildImageRequest;
use App\Http\Resources\InvitationStoryResource;
use App\Models\Invitation;
use App\Models\InvitationStory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Timeline entries behind the StoryEditor island (19.2).
 */
final class InvitationStoryController extends Controller
{
    public function store(
        StoreInvitationStoryRequest $request,
        Invitation $invitation,
        SaveInvitationStory $save,
    ): JsonResponse {
        return InvitationStoryResource::make($save($invitation, $request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateInvitationStoryRequest $request,
        InvitationStory $story,
        SaveInvitationStory $save,
    ): InvitationStoryResource {
        $story->loadMissing('invitation');

        return InvitationStoryResource::make($save($story->invitation, $request->validated(), $story));
    }

    public function destroy(InvitationStory $story): Response
    {
        Gate::authorize('delete', $story);

        $image = $story->image;

        $story->delete();

        if ($image !== null) {
            Storage::disk(InvitationStory::IMAGE_DISK)->delete($image);
        }

        return response()->noContent();
    }

    public function reorder(
        ReorderInvitationChildrenRequest $request,
        Invitation $invitation,
        ReorderInvitationChildren $reorder,
    ): JsonResponse {
        return response()->json(['moved' => $reorder($invitation->stories(), $request->ids())]);
    }

    public function image(UploadChildImageRequest $request, InvitationStory $story): InvitationStoryResource
    {
        $previous = $story->image;

        $path = $request->file('image')->store(
            InvitationStory::IMAGE_DIRECTORY,
            InvitationStory::IMAGE_DISK,
        );

        $story->update(['image' => $path]);

        if ($previous !== null && $previous !== $path) {
            Storage::disk(InvitationStory::IMAGE_DISK)->delete($previous);
        }

        return InvitationStoryResource::make($story->refresh());
    }
}
