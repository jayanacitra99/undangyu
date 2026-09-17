<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Actions\Media\AttachLibraryAudio;
use App\Enums\FeatureKey;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Support\EmbedLink;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A video embed or a library track (18.5, 18.6).
 *
 * Neither stores a file, so neither touches the photo or video quota. What is
 * checked instead is entitlement: `music` for a track, and for a video only
 * that the link is one we can actually embed.
 */
class StoreEmbeddedMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [InvitationMedia::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['video', 'audio'])],

            'url' => [
                Rule::requiredIf(fn (): bool => $this->input('kind') === 'video'),
                'nullable',
                'string',
                'url',
                'max:500',
                fn (string $attribute, mixed $value, Closure $fail) => is_string($value) && ! EmbedLink::isSupported($value)
                    ? $fail(__('Tautan harus dari YouTube atau Vimeo.'))
                    : null,
            ],

            'track' => [
                Rule::requiredIf(fn (): bool => $this->input('kind') === 'audio'),
                'nullable',
                'string',
                'max:100',
                fn (string $attribute, mixed $value, Closure $fail) => is_string($value) && AttachLibraryAudio::track($value) === null
                    ? $fail(__('Lagu tidak ada dalam pustaka.'))
                    : null,
            ],

            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->input('kind') !== 'audio') {
            return;
        }

        abort_if(
            $this->invitation()->entitlement(FeatureKey::Music) !== true,
            403,
            __('Paket ini belum termasuk musik latar.'),
        );
    }

    private function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
