<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\InvitationMedia;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing a gallery item in place (18.4).
 *
 * Only the caption is editable here. The cover is its own endpoint, because
 * setting one clears another row and that is not a field edit.
 */
class UpdateInvitationMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->media()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'caption' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    private function media(): InvitationMedia
    {
        $media = $this->route('media');

        abort_unless($media instanceof InvitationMedia, 404);

        return $media;
    }
}
