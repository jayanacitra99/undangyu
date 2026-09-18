<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Moving a selection of guests into a group, or out of every group (24.5).
 *
 * A null `guest_group_id` is the "ungroup these" case and is deliberately
 * allowed: it is the only way back out of a group without visiting forty rows.
 */
class BulkAssignGuestGroupRequest extends FormRequest
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
            'guest_group_id' => [
                'present',
                'nullable',
                Rule::exists('guest_groups', 'id')->where('invitation_id', $this->invitation()->getKey()),
            ],
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

    public function groupId(): ?int
    {
        $id = $this->validated()['guest_group_id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
