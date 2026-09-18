<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Facades\Setting;
use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A guestbook message from the invitation (M6.4, 29.2).
 *
 * Public, so the gate is structural: the route is rate limited, the invitation
 * has to be live with the guestbook on, and the length cap is a setting rather
 * than a constant because the right number is the one the operator learns.
 *
 * The message itself is not sanitised here. Angle brackets are part of what
 * somebody typed; the renderer escapes everything it prints, and stripping on
 * the way in would only teach the next reader of the column to trust it.
 */
class StoreWishRequest extends FormRequest
{
    /**
     * The hidden field no human fills in. A bot that fills every input it
     * finds gets a cheerful 201 and writes nothing.
     */
    public const HONEYPOT = 'website';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:2', 'max:'.$this->maxLength()],
            'token' => ['nullable', 'string', 'max:80'],
            // Present-and-empty rather than absent: a form that never rendered
            // the field is a form that did not come from our page.
            self::HONEYPOT => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama', 'message' => 'ucapan'];
    }

    public function isBot(): bool
    {
        return trim((string) $this->input(self::HONEYPOT)) !== '';
    }

    /**
     * The guest behind the token, when there is one — so a client can see that
     * the message came from someone on their list.
     */
    public function guest(): ?Guest
    {
        $token = trim((string) $this->input('token'));

        if ($token === '') {
            return null;
        }

        return Guest::query()
            ->where('token', $token)
            ->where('invitation_id', $this->invitation()->getKey())
            ->first();
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('publicInvitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }

    private function maxLength(): int
    {
        return (int) Setting::get('wishes.max_length', 500);
    }
}
