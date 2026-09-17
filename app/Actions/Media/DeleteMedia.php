<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\InvitationMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes one piece of media and the files behind it (18.4).
 *
 * The row goes first, inside a transaction; the files go after it commits. A
 * failed delete then leaves an orphan file, which costs disk — the other order
 * leaves a row pointing at nothing, which costs the client a broken gallery.
 *
 * Deleting the cover promotes the next photo, because an invitation with no
 * cover cannot publish and the client did not ask to unpublish anything.
 */
final class DeleteMedia
{
    public function __invoke(InvitationMedia $media): void
    {
        $media->loadMissing('invitation');

        $disk = $media->disk;

        // Only an upload owns its files. A library track's path is our shared
        // copy, played by every invitation that picked it — removing the row
        // must not take the file out from under the others.
        $paths = $media->source->consumesStorage() ? $media->storedPaths() : [];

        DB::transaction(function () use ($media): void {
            $wasCover = $media->is_cover;

            $media->delete();

            if (! $wasCover) {
                return;
            }

            $next = $media->invitation->media()
                ->where('type', MediaType::Image)
                ->orderBy('sort_order')
                ->first();

            $next?->update(['is_cover' => true]);
        });

        if ($disk === null || $paths === []) {
            return;
        }

        Storage::disk($disk)->delete($paths);
    }
}
