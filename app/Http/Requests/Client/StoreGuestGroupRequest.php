<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\GuestGroup;
use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new guest group (M5.2).
 *
 * The name is unique within the invitation: two groups called "Kantor" are a
 * mistake every time, and the client filtering by one of them would never know
 * which half of their list they were looking at.
 */
class StoreGuestGroupRequest extends FormRequest
{
    public const MAX_GROUPS = 50;

    public function authorize(): bool
    {
        return $this->user()?->can('create', [GuestGroup::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('guest_groups', 'name')->where('invitation_id', $this->invitation()->getKey()),
            ],
            // Hex only, and validated rather than trusted: the value is
            // rendered into a style attribute on the guest table, and a colour
            // column is not a place to accept arbitrary CSS.
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Grup dengan nama ini sudah ada.',
            'color.regex' => 'Warna harus berupa kode heksadesimal seperti #0d6efd.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama grup', 'color' => 'warna'];
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
