<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Rsvp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One answer, as the guest's own form reads it back (M6.3).
 *
 * No ip_hash and no user agent: they exist for abuse review, and a guest's
 * browser has no business being told what we recorded about it.
 *
 * @mixin Rsvp
 */
class RsvpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'attendance' => $this->attendance->value,
            'attendance_label' => $this->attendance->label(),
            'pax' => $this->pax,
            'meal_preference' => $this->meal_preference,
            'notes' => $this->notes,
            'invitation_event_id' => $this->invitation_event_id,
            'responded_at' => $this->responded_at->toIso8601String(),
        ];
    }
}
