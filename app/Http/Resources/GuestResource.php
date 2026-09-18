<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Guest;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the guest table (M5.1, M5.10).
 *
 * The token goes out: this is the client's own screen, and the personalised
 * link is the whole point of the row. `whatsapp_phone` is the digits-only form
 * Session 26's `wa.me/` link needs, resolved here so the table does not
 * reimplement the normalisation in JavaScript.
 *
 * @mixin Guest
 */
class GuestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'name' => $this->name,
            'display_name' => $this->displayName(),
            'phone' => $this->phone,
            'whatsapp_phone' => PhoneNumber::forWhatsApp($this->phone),
            'email' => $this->email,
            'address' => $this->address,
            'token' => $this->token,
            'guest_group_id' => $this->guest_group_id,
            // whenLoaded, because the table eager-loads the group and the
            // single-row responses after a save do not.
            'group' => GuestGroupResource::make($this->whenLoaded('group')),
            'max_pax' => $this->max_pax,
            'is_vip' => $this->is_vip,
            'table_number' => $this->table_number,
            'notes' => $this->notes,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'open_count' => $this->open_count,
        ];
    }
}
