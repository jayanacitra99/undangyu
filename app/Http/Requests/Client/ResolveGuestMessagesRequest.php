<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Give me the message for each of these guests" (26.4, 26.5).
 *
 * Either a saved template or a raw body — the editor previews text that has
 * not been saved yet, and the bulk workflow sends one that has. A body wins if
 * both arrive, because it is the newer of the two by definition.
 */
class ResolveGuestMessagesRequest extends FormRequest
{
    public const MAX_GUESTS = 500;

    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->invitation()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer'],
            'body' => ['nullable', 'string', 'max:'.StoreMessageTemplateRequest::MAX_BODY],
            'ids' => ['nullable', 'array', 'max:'.self::MAX_GUESTS],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var list<int> $ids */
        $ids = array_map('intval', $this->validated()['ids'] ?? []);

        return $ids;
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
