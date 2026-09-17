<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvitationEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One event session, as the builder island reads them (M4.4).
 *
 * Times go out as wall-clock in the invitation's timezone, in the shape a
 * `datetime-local` input wants — the same frame they come back in. The stored
 * UTC value is never shown to the client; there is no screen where "19:00 in
 * Jakarta" should read as 12:00.
 *
 * @mixin InvitationEvent
 */
class InvitationEventResource extends JsonResource
{
    public const INPUT_FORMAT = 'Y-m-d\TH:i';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->local_start_at->format(self::INPUT_FORMAT),
            'end_at' => $this->local_end_at?->format(self::INPUT_FORMAT),
            'is_all_day' => $this->is_all_day,
            'venue_name' => $this->venue_name,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'dress_code' => $this->dress_code,
            'live_stream_url' => $this->live_stream_url,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
        ];
    }
}
