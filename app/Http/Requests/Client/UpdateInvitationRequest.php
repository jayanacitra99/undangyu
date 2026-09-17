<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Dasar" tab and the autosave endpoint behind it (16.3, 16.4).
 *
 * Every rule is `sometimes`, because an autosave sends only the field that
 * changed. A missing key means "leave it alone"; a present-but-empty one is a
 * real edit and is validated as one.
 *
 * The slug is the public URL and is the only field here with consequences
 * outside the invitation: it has to survive being someone else's address, a
 * reserved word, and a soft-deleted row still holding it.
 */
class UpdateInvitationRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:150'],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:120',
                // Lowercase, digits and single hyphens between them. The
                // public route pattern is built on the same shape.
                'regex:/^[a-z0-9]([a-z0-9\-]{1,118}[a-z0-9])$/',
                // withoutTrashed() is deliberately absent: a soft-deleted
                // invitation still owns its address until it is purged, and
                // restoring one must not collide with a live site.
                Rule::unique('invitations', 'slug')->ignore($this->invitation()->getKey()),
                fn (string $attribute, mixed $value, Closure $fail) => is_string($value)
                    && Invitation::slugIsBlocked($value)
                    ? $fail(__('Alamat ini sudah dipakai sistem. Pilih yang lain.'))
                    : null,
            ],

            'language' => ['sometimes', 'required', 'string', Rule::in(array_keys(Invitation::LANGUAGES))],
            'timezone' => ['sometimes', 'required', 'string', Rule::in(array_keys(Invitation::TIMEZONES))],

            'meta_title' => ['sometimes', 'nullable', 'string', 'max:70'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Alamat hanya boleh huruf kecil, angka dan tanda hubung.',
            'slug.unique' => 'Alamat ini sudah dipakai. Pilih yang lain.',
            'meta_title.max' => 'Judul SEO maksimal 70 karakter agar tidak terpotong.',
            'meta_description.max' => 'Deskripsi SEO maksimal 160 karakter agar tidak terpotong.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul undangan',
            'slug' => 'alamat undangan',
            'language' => 'bahasa',
            'timezone' => 'zona waktu',
            'meta_title' => 'judul SEO',
            'meta_description' => 'deskripsi SEO',
        ];
    }

    private function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
