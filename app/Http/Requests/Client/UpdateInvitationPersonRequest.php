<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationPerson;

/**
 * An autosaved edit to one person (M4.3).
 *
 * Extends the store rules and makes every one of them `sometimes`: the island
 * sends the field that changed, so a missing key means "leave it alone".
 */
class UpdateInvitationPersonRequest extends StoreInvitationPersonRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->person()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $ruleSet) {
            array_unshift($ruleSet, 'sometimes');
            $rules[$field] = $ruleSet;
        }

        return $rules;
    }

    /**
     * The person's own invitation, since this route carries no invitation
     * segment — the role check still has to run against the right event type.
     */
    protected function invitation(): Invitation
    {
        return $this->person()->invitation;
    }

    private function person(): InvitationPerson
    {
        $person = $this->route('person');

        abort_unless($person instanceof InvitationPerson, 404);

        return $person;
    }
}
