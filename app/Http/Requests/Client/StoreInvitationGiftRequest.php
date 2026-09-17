<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\GiftType;
use App\Models\Invitation;
use App\Models\InvitationGift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A gift destination (M4.7).
 *
 * The required fields depend on the type: a bank transfer needs an account
 * number, a postal gift needs a recipient and an address, and QRIS needs
 * neither because the image carries both.
 */
class StoreInvitationGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [InvitationGift::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::enum(GiftType::class)],

            'provider_name' => [
                Rule::requiredIf(fn (): bool => in_array($type, [GiftType::Bank->value, GiftType::Ewallet->value], true)),
                'nullable', 'string', 'max:120',
            ],
            'account_name' => [
                Rule::requiredIf(fn (): bool => in_array($type, [GiftType::Bank->value, GiftType::Ewallet->value], true)),
                'nullable', 'string', 'max:190',
            ],
            'account_number' => [
                Rule::requiredIf(fn (): bool => in_array($type, [GiftType::Bank->value, GiftType::Ewallet->value], true)),
                'nullable', 'string', 'max:60',
            ],

            'recipient_name' => [
                Rule::requiredIf(fn (): bool => $type === GiftType::Address->value),
                'nullable', 'string', 'max:190',
            ],
            'address' => [
                Rule::requiredIf(fn (): bool => $type === GiftType::Address->value),
                'nullable', 'string', 'max:500',
            ],

            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis hadiah',
            'provider_name' => 'nama bank atau dompet',
            'account_name' => 'nama pemilik',
            'account_number' => 'nomor rekening',
            'recipient_name' => 'nama penerima',
            'address' => 'alamat',
            'notes' => 'catatan',
        ];
    }

    protected function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
