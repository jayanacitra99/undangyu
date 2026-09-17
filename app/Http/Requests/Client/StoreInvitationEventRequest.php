<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new dated occasion on an invitation (M4.4).
 *
 * `start_at` and `end_at` arrive as wall-clock times in the invitation's own
 * timezone — that is what the client typed and what the guest will read. The
 * action converts them to UTC for storage; nothing here does, because
 * validation should compare what was typed against what was typed.
 */
class StoreInvitationEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [InvitationEvent::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'is_all_day' => ['boolean'],
            'venue_name' => ['required', 'string', 'max:190'],
            'address' => ['nullable', 'string', 'max:2000'],
            'maps_url' => ['nullable', 'string', 'url', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'dress_code' => ['nullable', 'string', 'max:190'],
            'live_stream_url' => ['nullable', 'string', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama acara',
            'start_at' => 'waktu mulai',
            'end_at' => 'waktu selesai',
            'venue_name' => 'nama tempat',
            'address' => 'alamat',
            'maps_url' => 'tautan Maps',
            'dress_code' => 'dress code',
            'live_stream_url' => 'tautan live streaming',
        ];
    }

    protected function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
