<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\GuestGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Renaming or recolouring a group (M5.2).
 */
class UpdateGuestGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->group()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $group = $this->group();

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('guest_groups', 'name')
                    ->where('invitation_id', $group->invitation_id)
                    ->ignore($group->getKey()),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
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

    protected function group(): GuestGroup
    {
        $group = $this->route('group');

        abort_unless($group instanceof GuestGroup, 404);

        return $group;
    }
}
