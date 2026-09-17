<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvitationPerson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * One person, as the builder island reads them (M4.3).
 *
 * @mixin InvitationPerson
 */
class InvitationPersonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'bio' => $this->bio,
            'parent_father' => $this->parent_father,
            'parent_mother' => $this->parent_mother,
            'child_order' => $this->child_order,
            'instagram' => $this->instagram,
            'photo' => $this->photo,
            'photo_url' => $this->photo === null ? null : Storage::disk(InvitationPerson::PHOTO_DISK)->url($this->photo),
            'sort_order' => $this->sort_order,
        ];
    }
}
