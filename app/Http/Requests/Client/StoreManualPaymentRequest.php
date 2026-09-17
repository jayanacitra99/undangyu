<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The client's transfer proof (M2.7).
 *
 * Images and PDFs only, 5MB, mime-checked rather than extension-checked — the
 * file lands on a private disk and is streamed back to an admin, so a
 * mislabelled upload is worth refusing at the door.
 */
class StoreManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // `pay` is ownership plus an order that is still open, which is
        // exactly who may send money for it.
        return $this->user()?->can('pay', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proof' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:5120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.required' => 'Unggah bukti transfer terlebih dahulu.',
            'proof.mimetypes' => 'Bukti transfer harus berupa JPG, PNG, WEBP atau PDF.',
            'proof.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ];
    }
}
