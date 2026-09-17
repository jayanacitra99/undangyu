<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationGift;

/**
 * An autosaved edit to one gift destination (M4.7).
 *
 * `sometimes` throughout, and the conditional required rules read the type off
 * the stored row when the payload does not carry one — editing an account
 * number must still be checked as a bank account.
 */
class UpdateInvitationGiftRequest extends StoreInvitationGiftRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->gift()) ?? false;
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

    protected function prepareForValidation(): void
    {
        if (! $this->has('type')) {
            $this->merge(['type' => $this->gift()->type->value]);
        }
    }

    protected function invitation(): Invitation
    {
        return $this->gift()->invitation;
    }

    private function gift(): InvitationGift
    {
        $gift = $this->route('gift');

        abort_unless($gift instanceof InvitationGift, 404);

        return $gift;
    }
}
