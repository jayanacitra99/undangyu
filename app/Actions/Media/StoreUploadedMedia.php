<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaJob;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Takes one uploaded file into an invitation's gallery (18.2).
 *
 * The row is written with the original's size and no conversions, then the
 * queue makes the renditions (hard rule 9). So the client sees their photo in
 * the grid immediately, at full size, and it gets lighter a few seconds later
 * — rather than watching a spinner while a 8MB phone photo is resized.
 */
final class StoreUploadedMedia
{
    public function __invoke(
        Invitation $invitation,
        UploadedFile $file,
        MediaType $type,
        ?string $caption = null,
    ): InvitationMedia {
        $path = $file->store($this->directory($invitation), InvitationMedia::UPLOAD_DISK);

        $media = DB::transaction(function () use ($invitation, $path, $file, $type, $caption): InvitationMedia {
            return $invitation->media()->create([
                'type' => $type,
                'source' => MediaSource::Upload,
                'disk' => InvitationMedia::UPLOAD_DISK,
                'path' => $path,
                'caption' => $caption,
                'file_size' => $file->getSize(),
                // The first photo on an empty gallery is the cover, because an
                // invitation with no cover cannot publish and nobody wants to
                // be told that later.
                'is_cover' => $type === MediaType::Image && ! $invitation->media()->where('is_cover', true)->exists(),
                'sort_order' => ((int) ($invitation->media()->max('sort_order') ?? -1)) + 1,
            ]);
        });

        if ($type === MediaType::Image) {
            ProcessMediaJob::dispatch($media->getKey());
        }

        return $media;
    }

    /**
     * One folder per invitation, so deleting an invitation's files is one
     * call and nothing collides across tenants.
     */
    private function directory(Invitation $invitation): string
    {
        return 'invitations/'.$invitation->getKey().'/media';
    }
}
