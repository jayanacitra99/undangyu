<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationStory;

/**
 * An autosaved edit to one timeline entry (M4.5).
 */
class UpdateInvitationStoryRequest extends StoreInvitationStoryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->story()) ?? false;
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

    protected function invitation(): Invitation
    {
        return $this->story()->invitation;
    }

    private function story(): InvitationStory
    {
        $story = $this->route('story');

        abort_unless($story instanceof InvitationStory, 404);

        return $story;
    }
}
