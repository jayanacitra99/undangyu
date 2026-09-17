<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvitationGift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * One gift destination, as the GiftsEditor island reads it (M4.7).
 *
 * The account number goes out in full: this is the client's own screen, and
 * the number is printed on their invitation for guests to copy. What the
 * `encrypted` cast protects is the database, not this response.
 *
 * @mixin InvitationGift
 */
class InvitationGiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'provider_name' => $this->provider_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'qris_image' => $this->qris_image,
            'qris_url' => $this->qris_image === null
                ? null
                : Storage::disk(InvitationGift::IMAGE_DISK)->url($this->qris_image),
            'recipient_name' => $this->recipient_name,
            'address' => $this->address,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
        ];
    }
}
