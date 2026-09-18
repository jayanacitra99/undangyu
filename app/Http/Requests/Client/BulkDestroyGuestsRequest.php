<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk delete from the guest table (24.4).
 *
 * The ids are only a claim. Which of them actually belong to this invitation
 * is decided by the query in the controller, so a crafted payload deletes
 * nothing that is not the client's own — the same rule the reorder endpoints
 * follow.
 */
class BulkDestroyGuestsRequest extends FormRequest
{
    public const MAX_IDS = 500;

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
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
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
