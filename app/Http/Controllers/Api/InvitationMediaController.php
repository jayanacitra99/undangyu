<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invitations\ReorderInvitationChildren;
use App\Actions\Media\AttachEmbeddedVideo;
use App\Actions\Media\AttachLibraryAudio;
use App\Actions\Media\DeleteMedia;
use App\Actions\Media\SetCoverMedia;
use App\Actions\Media\StoreUploadedMedia;
use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ReorderInvitationChildrenRequest;
use App\Http\Requests\Client\StoreEmbeddedMediaRequest;
use App\Http\Requests\Client\StoreInvitationMediaRequest;
use App\Http\Requests\Client\UpdateInvitationMediaRequest;
use App\Http\Resources\InvitationMediaResource;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Services\Media\MediaQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The gallery's endpoints (18.2, 18.4, 18.5, 18.6).
 *
 * Every response that changes what is stored carries the quota back with it,
 * so the meter above the grid is right without a second request — and so the
 * client sees "28 of 30" tick up as they upload rather than finding out at 31.
 */
final class InvitationMediaController extends Controller
{
    public function __construct(private readonly MediaQuota $quota) {}

    public function store(
        StoreInvitationMediaRequest $request,
        Invitation $invitation,
        StoreUploadedMedia $store,
    ): JsonResponse {
        $media = $store(
            $invitation,
            $request->file('file'),
            MediaType::from((string) $request->validated('type')),
            $request->validated('caption'),
        );

        return $this->itemResponse($media, $invitation, Response::HTTP_CREATED);
    }

    /**
     * A YouTube or Vimeo video, or a track from the curated library. Neither
     * stores a file, so neither goes through the upload path.
     */
    public function storeEmbedded(
        StoreEmbeddedMediaRequest $request,
        Invitation $invitation,
        AttachEmbeddedVideo $attachVideo,
        AttachLibraryAudio $attachAudio,
    ): JsonResponse {
        $media = $request->validated('kind') === 'video'
            ? $attachVideo($invitation, (string) $request->validated('url'), $request->validated('caption'))
            : $attachAudio($invitation, (string) $request->validated('track'));

        return $this->itemResponse($media, $invitation, Response::HTTP_CREATED);
    }

    public function update(UpdateInvitationMediaRequest $request, InvitationMedia $media): InvitationMediaResource
    {
        $media->update($request->validated());

        return InvitationMediaResource::make($media);
    }

    public function cover(InvitationMedia $media, SetCoverMedia $setCover): InvitationMediaResource
    {
        Gate::authorize('update', $media);

        return InvitationMediaResource::make($setCover($media));
    }

    public function destroy(InvitationMedia $media, DeleteMedia $delete): JsonResponse
    {
        Gate::authorize('delete', $media);

        $media->loadMissing('invitation');
        $invitation = $media->invitation;

        $delete($media);

        return response()->json(['quota' => $this->quota->summary($invitation)]);
    }

    public function reorder(
        ReorderInvitationChildrenRequest $request,
        Invitation $invitation,
        ReorderInvitationChildren $reorder,
    ): JsonResponse {
        return response()->json(['moved' => $reorder($invitation->media(), $request->ids())]);
    }

    private function itemResponse(InvitationMedia $media, Invitation $invitation, int $status): JsonResponse
    {
        return InvitationMediaResource::make($media)
            ->additional(['quota' => $this->quota->summary($invitation)])
            ->response()
            ->setStatusCode($status);
    }
}
