<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\FeatureKey;
use App\Enums\MediaType;
use App\Facades\Setting;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Services\Media\MediaQuota;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One uploaded file for the gallery (18.2).
 *
 * Three gates, in this order: is it a file we accept, is it small enough, and
 * is there room for it. The quota check is here rather than in the controller
 * because hard rule 10 says so, and because the point of checking is to refuse
 * before the file is moved anywhere.
 *
 * The mime allowlist and the size cap are seeded settings, so raising the cap
 * for a campaign is an admin edit and not a deploy.
 */
class StoreInvitationMediaRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                // mimetypes, not mimes: the extension is whatever the client
                // typed, the sniffed type is what the file actually is.
                'mimetypes:'.implode(',', $this->allowedMimes()),
                'max:'.$this->maxKilobytes(),
            ],
            'type' => ['required', Rule::enum(MediaType::class)],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimetypes' => 'Jenis berkas ini tidak didukung.',
            'file.max' => 'Ukuran berkas maksimal :max KB.',
        ];
    }

    /**
     * The quota is not a field rule: it is about what is already stored, so it
     * runs after the file itself is known to be acceptable.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $invitation = $this->invitation();
            $quota = app(MediaQuota::class);
            $type = MediaType::from((string) $this->input('type'));

            if ($type === MediaType::Image && ! $quota->hasPhotoRoom($invitation)) {
                $validator->errors()->add('file', __(
                    'Kuota foto paket ini sudah penuh (:limit foto). Tingkatkan paket untuk menambah foto.',
                    ['limit' => (string) $quota->photoLimit($invitation)],
                ));

                return;
            }

            if ($type === MediaType::Video) {
                $this->checkVideo($validator, $invitation, $quota);
            }

            if ($type === MediaType::Audio && $invitation->entitlement(FeatureKey::Music) !== true) {
                $validator->errors()->add('file', __('Paket ini belum termasuk musik latar.'));
            }
        });
    }

    private function checkVideo(Validator $validator, Invitation $invitation, MediaQuota $quota): void
    {
        $limit = $quota->videoMbLimit($invitation);

        if ($limit === 0) {
            $validator->errors()->add('file', __('Paket ini belum termasuk unggah video.'));

            return;
        }

        $size = (int) ($this->file('file')?->getSize() ?? 0);

        if (! $quota->hasVideoRoom($invitation, $size)) {
            $validator->errors()->add('file', __(
                'Kuota video paket ini sudah penuh (:limit MB). Tingkatkan paket untuk menambah video.',
                ['limit' => (string) $limit],
            ));
        }
    }

    /**
     * @return list<string>
     */
    private function allowedMimes(): array
    {
        /** @var list<string> $mimes */
        $mimes = Setting::get('media.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp']);

        return $mimes;
    }

    private function maxKilobytes(): int
    {
        return ((int) Setting::get('media.max_upload_mb', 10)) * 1024;
    }

    private function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
