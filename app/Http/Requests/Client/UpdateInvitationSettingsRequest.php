<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\InvitationVisibility;
use App\Models\Invitation;
use App\Support\InvitationSettings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Pengaturan" tab (19.5).
 *
 * The toggles are checked against InvitationSettings — both that the key
 * exists and that the package sold the feature. An unchecked HTML checkbox
 * sends nothing at all, so the form posts every toggle explicitly and this
 * reads them as booleans.
 */
class UpdateInvitationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->invitation()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rsvp_enabled' => ['required', 'boolean'],
            'guestbook_enabled' => ['required', 'boolean'],
            'guestbook_moderation' => ['required', Rule::in(array_keys(InvitationSettings::MODERATION_MODES))],
            'music_enabled' => ['required', 'boolean'],
            'music_autoplay' => ['required', 'boolean'],
            'countdown_enabled' => ['required', 'boolean'],
            'gift_enabled' => ['required', 'boolean'],

            'visibility' => ['required', Rule::enum(InvitationVisibility::class)],
            'password' => [
                // Required only when turning password protection on for an
                // invitation that has none — an already-protected one keeps
                // its password if the field is left blank.
                Rule::requiredIf(fn (): bool => $this->input('visibility') === InvitationVisibility::Password->value
                    && $this->invitation()->password === null),
                'nullable',
                'string',
                'min:4',
                'max:100',
            ],
        ];
    }

    /**
     * An unchecked checkbox is absent from the payload, which `boolean` reads
     * as a failure rather than as false. Filling them in first is what makes
     * the toggles behave like toggles.
     */
    protected function prepareForValidation(): void
    {
        $booleans = [];

        foreach (InvitationSettings::keys() as $key) {
            if ($key === 'guestbook_moderation') {
                continue;
            }

            $booleans[$key] = $this->boolean($key);
        }

        $this->merge($booleans);
    }

    /**
     * A toggle the package never sold cannot be switched on here, whatever the
     * form posts (hard rule 10).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invitation = $this->invitation();

            foreach (InvitationSettings::availability($invitation) as $key => $available) {
                if (! $available && $this->boolean($key)) {
                    $validator->errors()->add($key, __('Paket ini belum termasuk fitur tersebut.'));
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Isi kata sandi untuk mengunci undangan.',
            'password.min' => 'Kata sandi minimal 4 karakter.',
        ];
    }

    private function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
