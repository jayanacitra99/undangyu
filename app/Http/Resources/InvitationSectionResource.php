<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\SectionKey;
use App\Models\InvitationSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One section row, as the SectionsManager island reads it (M4.11).
 *
 * @mixin InvitationSection
 */
class InvitationSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_key' => $this->section_key->value,
            'default_label' => $this->section_key->label(),
            'heading' => $this->heading(),
            'title' => $this->title,
            'body' => $this->content['body'] ?? null,
            'is_visible' => $this->is_visible,
            'is_custom' => $this->section_key === SectionKey::Custom,
            'sort_order' => $this->sort_order,
        ];
    }
}
