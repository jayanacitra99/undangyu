<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * An admin's decision note on a manual transfer (M2.7).
 *
 * Approval may carry a note; rejection must — the client reads it to know what
 * to fix, and "rejected, no reason" is an unanswerable support ticket.
 */
class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('verify', $this->route('payment')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $noteRules = ['string', 'max:500'];

        return [
            'note' => $this->isRejection()
                ? ['required', ...$noteRules]
                : ['nullable', ...$noteRules],
        ];
    }

    public function note(): ?string
    {
        $note = $this->validated('note');

        return $note === '' ? null : $note;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => 'Alasan penolakan wajib diisi agar klien tahu apa yang harus diperbaiki.',
        ];
    }

    private function isRejection(): bool
    {
        return $this->routeIs('admin.payments.reject');
    }
}
