<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Contracts\BelongsToInvitation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An image belonging to one invitation child — a story photo, a QRIS code
 * (19.1, 19.2).
 *
 * Mime-checked rather than extension-checked, 5MB, same as a person's photo.
 * These are single columns rather than gallery rows, so they carry no
 * conversions and count against no quota.
 */
class UploadChildImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->child()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Pilih gambar terlebih dahulu.',
            'image.mimetypes' => 'Gambar harus berupa JPG, PNG atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
        ];
    }

    /**
     * Whichever child the route bound — the rule is the same for all of them.
     */
    private function child(): BelongsToInvitation
    {
        foreach (['story', 'gift'] as $parameter) {
            $child = $this->route($parameter);

            if ($child instanceof BelongsToInvitation) {
                return $child;
            }
        }

        abort(404);
    }
}
