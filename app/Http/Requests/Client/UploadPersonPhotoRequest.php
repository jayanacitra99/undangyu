<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\InvitationPerson;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A person's photo (M4.3).
 *
 * Mime-checked rather than extension-checked, and capped at 5MB: these come
 * straight off a phone camera, where 8MB is ordinary. The gallery's real
 * pipeline — conversions, storage accounting — is Session 18; this is one
 * column holding one path.
 */
class UploadPersonPhotoRequest extends FormRequest
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
        return [
            'photo' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Pilih foto terlebih dahulu.',
            'photo.mimetypes' => 'Foto harus berupa JPG, PNG atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    private function person(): InvitationPerson
    {
        $person = $this->route('person');

        abort_unless($person instanceof InvitationPerson, 404);

        return $person;
    }
}
