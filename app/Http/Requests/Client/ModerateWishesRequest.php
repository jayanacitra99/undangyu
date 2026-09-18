<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One moderation action over a selection of wishes (29.4).
 *
 * The action is an allowlist, not a method name: a string from a form reaching
 * a match arm is fine, reaching anything dynamic is not.
 */
class ModerateWishesRequest extends FormRequest
{
    public const ACTIONS = ['approve', 'reject', 'pin', 'unpin', 'delete'];

    public const MAX_IDS = 200;

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
            'action' => ['required', Rule::in(self::ACTIONS)],
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function action(): string
    {
        return (string) $this->validated()['action'];
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
