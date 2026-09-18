<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One guestbook message as a guest reads it (M6.4).
 *
 * The message goes out raw and is escaped where it is printed — Vue's `{{ }}`
 * and Blade's `{{ }}` both escape, and the renderer never uses v-html. HTML-
 * encoding it here instead would double-escape the moment anything printed it
 * correctly, and would leave the column's contents depending on which layer
 * wrote them.
 *
 * No ip_hash and no status: a guest reading the feed sees names, messages and
 * when they were written.
 *
 * @mixin Wish
 */
class WishResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'message' => $this->message,
            'is_pinned' => $this->is_pinned,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
