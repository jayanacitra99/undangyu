<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvitationStory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * One timeline entry, as the StoryEditor island reads it (M4.5).
 *
 * @mixin InvitationStory
 */
class InvitationStoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            // The shape a date input wants; the column is a plain date.
            'date' => $this->date?->format('Y-m-d'),
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->image === null
                ? null
                : Storage::disk(InvitationStory::IMAGE_DISK)->url($this->image),
            'sort_order' => $this->sort_order,
        ];
    }
}
