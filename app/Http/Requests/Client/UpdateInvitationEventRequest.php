<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationEvent;

/**
 * An autosaved edit to one event session (M4.4).
 *
 * Every rule becomes `sometimes`, except the pairing between start and end:
 * `after:start_at` cannot compare against a field the payload did not send, so
 * the missing side is filled from the stored row before validation runs.
 */
class UpdateInvitationEventRequest extends StoreInvitationEventRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->event()) ?? false;
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
        // An island editing only `end_at` still has to be checked against the
        // start it is not sending. The stored value is rendered back into the
        // invitation's timezone first, because that is the frame the incoming
        // value is in.
        if ($this->has('end_at') && ! $this->has('start_at')) {
            $this->merge([
                'start_at' => $this->event()->local_start_at->format('Y-m-d H:i:s'),
            ]);
        }
    }

    protected function invitation(): Invitation
    {
        return $this->event()->invitation;
    }

    private function event(): InvitationEvent
    {
        $event = $this->route('event');

        abort_unless($event instanceof InvitationEvent, 404);

        // local_start_at reads the timezone off the inverse relation and falls
        // back to the app's when it is absent — which would compare a Jakarta
        // time against a UTC one and let a backwards end time through.
        $event->loadMissing('invitation');

        return $event;
    }
}
