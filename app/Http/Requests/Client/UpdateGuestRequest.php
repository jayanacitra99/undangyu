<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An edit to one guest (M5.1).
 *
 * No quota check: editing a guest cannot grow the list. `token` is absent from
 * the rules on purpose — it is generated once and is not the client's to
 * change, because the link is already out there.
 */
class UpdateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->guest()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return GuestRules::fields($this->invitation(), required: false);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return GuestRules::ATTRIBUTES;
    }

    protected function guest(): Guest
    {
        $guest = $this->route('guest');

        abort_unless($guest instanceof Guest, 404);

        return $guest;
    }

    /**
     * The guest's own invitation, not a route parameter: this endpoint is
     * bound by the guest alone, and the group rule needs to know which
     * invitation's groups are allowed.
     */
    protected function invitation(): Invitation
    {
        $invitation = $this->guest()->loadMissing('invitation')->invitation;

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
