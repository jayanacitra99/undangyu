<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A drag-reorder of one invitation's children (M4.3, M4.4, M4.11).
 *
 * The payload is the ids in their new order. Which table they belong to is the
 * route's business, but the ids must all belong to *this* invitation — the
 * action re-checks that, so a reorder can never adopt another client's row.
 */
class ReorderInvitationChildrenRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:200'],
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

    private function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
