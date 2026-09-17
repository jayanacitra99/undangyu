<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvitationMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One gallery item, as the GalleryEditor island reads it (18.4).
 *
 * URLs go out resolved, never paths: the island must not have to know which
 * disk a row sits on or whether the queue has made its renditions yet.
 *
 * @mixin InvitationMedia
 */
class InvitationMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'source' => $this->source->value,
            'caption' => $this->caption,
            'file_size' => $this->file_size,
            'is_cover' => $this->is_cover,
            'sort_order' => $this->sort_order,
            'url' => $this->url(),
            // Falls back to the original until the queue catches up, so a photo
            // is visible the moment it is uploaded.
            'thumb_url' => $this->thumbnailUrl(),
            'embed_url' => $this->embed_url,
            'is_processed' => $this->conversions !== null && $this->conversions !== [],
        ];
    }

    /**
     * An embed's poster frame is a remote URL; an upload's is a conversion.
     */
    private function thumbnailUrl(): ?string
    {
        if ($this->thumbnail !== null && str_starts_with($this->thumbnail, 'http')) {
            return $this->thumbnail;
        }

        return $this->conversion('thumb');
    }
}
