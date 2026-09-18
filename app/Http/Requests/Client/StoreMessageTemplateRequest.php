<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\MessageChannel;
use App\Models\Invitation;
use App\Models\MessageTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A message template of this invitation's own (26.2).
 *
 * The body is capped well under WhatsApp's own limit: a message longer than
 * this is not an invitation, and the variables expand it further.
 */
class StoreMessageTemplateRequest extends FormRequest
{
    public const MAX_BODY = 4000;

    public function authorize(): bool
    {
        return $this->user()?->can('create', [MessageTemplate::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['nullable', Rule::enum(MessageChannel::class)],
            'subject' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:'.self::MAX_BODY],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama templat', 'body' => 'isi pesan', 'subject' => 'subjek'];
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
