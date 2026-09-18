<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GuestGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One guest group, with the count the filter bar shows (M5.2).
 *
 * `guests_count` is only present when the caller asked for it with
 * withCount — a group returned alongside a saved guest has no business
 * running an aggregate.
 *
 * @mixin GuestGroup
 */
class GuestGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            // whenHas, because a group loaded as a guest's badge selects only
            // the three columns the badge draws.
            'sort_order' => $this->whenHas('sort_order'),
            'guests_count' => $this->whenCounted('guests'),
        ];
    }
}
