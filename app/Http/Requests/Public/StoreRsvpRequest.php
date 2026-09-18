<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\RsvpAttendance;
use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A guest confirming attendance (M6.1, M6.9, 28.2).
 *
 * Public, so `authorize()` is true and the gate is elsewhere: the route is
 * rate limited per IP per invitation, the invitation has to be live, and every
 * id in the payload is checked against that invitation rather than trusted.
 *
 * `pax` is the interesting rule. A named guest may bring what their invitation
 * allowed — `max_pax`, which the client set — and an anonymous responder is
 * capped at a sane default, because nobody who was handed the public link is
 * entitled to declare a party of forty.
 */
class StoreRsvpRequest extends FormRequest
{
    public const ANONYMOUS_MAX_PAX = 5;

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
            // Prefilled from the token, but still sent: a guest may correct
            // the spelling of their own name, and an anonymous one has to type
            // it.
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'attendance' => ['required', Rule::enum(RsvpAttendance::class)],
            'pax' => ['required', 'integer', 'min:0', 'max:'.$this->maxPax()],
            'meal_preference' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],

            // Which occasion, where the invitation has more than one. Must be
            // one of this invitation's own events; null means "all of them".
            'invitation_event_id' => [
                'nullable',
                Rule::exists('invitation_events', 'id')->where('invitation_id', $this->invitation()->getKey()),
            ],

            // The guest's own token, if they arrived on a personalised link.
            'token' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pax.max' => 'Undangan ini berlaku untuk maksimal :max orang.',
            'name.required' => 'Nama wajib diisi.',
            'attendance.required' => 'Pilih kehadiran Anda.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor telepon',
            'attendance' => 'kehadiran',
            'pax' => 'jumlah orang',
            'notes' => 'pesan',
        ];
    }

    /**
     * Someone who is not coming brings nobody. Saying "no, 3 people" is a typo
     * every time, and it would otherwise land in the head count.
     *
     * Before validation rather than after: a rule reads the input, and a value
     * corrected once the rules have run is a value only the rules ever saw.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('attendance') === RsvpAttendance::No->value) {
            $this->merge(['pax' => 0]);
        }
    }

    /**
     * The guest this submission belongs to, when the token resolves against
     * this invitation. Null for an anonymous responder.
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

    /**
     * What the guest was invited for, or the anonymous cap.
     */
    private function maxPax(): int
    {
        $guest = $this->guest();

        return $guest === null ? self::ANONYMOUS_MAX_PAX : $guest->max_pax;
    }
}
