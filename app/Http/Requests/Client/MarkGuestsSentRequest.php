<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "I have sent these ones" (26.5).
 *
 * Same shape as the other bulk endpoints and the same rule about the ids: they
 * are a claim, and the controller filters them through the invitation's own
 * relation, so a crafted payload marks nothing that is not the client's.
 */
class MarkGuestsSentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->invitation()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.BulkDestroyGuestsRequest::MAX_IDS],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var list<int> $ids */
        $ids = array_map('intval', $this->validated()['ids']);

        return $ids;
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
