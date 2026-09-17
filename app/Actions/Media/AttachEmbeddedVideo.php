<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Support\EmbedLink;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Adds a YouTube or Vimeo video to the gallery (18.5).
 *
 * An embed stores no file and counts against no quota — the video lives on
 * someone else's CDN. What is stored is the canonical embed URL, so a pasted
 * watch link, share link or iframe src all end up the same row.
 */
final class AttachEmbeddedVideo
{
    public function __invoke(Invitation $invitation, string $url, ?string $caption = null): InvitationMedia
    {
        $parsed = EmbedLink::parse($url);

        if ($parsed === null) {
            // The FormRequest refuses this first; reaching here means a caller
            // skipped it, and a wrong row is worse than an exception.
            throw new RuntimeException('Tautan video tidak dikenali.');
        }

        return DB::transaction(fn (): InvitationMedia => $invitation->media()->create([
            'type' => MediaType::Video,
            'source' => MediaSource::Embed,
            'disk' => null,
            'path' => null,
            'embed_url' => $parsed['embed_url'],
            'thumbnail' => $parsed['thumbnail'],
            'caption' => $caption,
            'file_size' => null,
            'is_cover' => false,
            'sort_order' => ((int) ($invitation->media()->max('sort_order') ?? -1)) + 1,
        ]));
    }
}
