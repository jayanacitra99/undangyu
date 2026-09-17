<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationPerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new person on an invitation (M4.3).
 *
 * The allowed roles are the event type's `person_roles`, not the whole
 * PersonRole enum: a graduation invitation has no bride. The schema cannot say
 * that — the valid set depends on the row's parent — so it is said here.
 */
class StoreInvitationPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [InvitationPerson::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in($this->allowedRoles())],
            'full_name' => ['required', 'string', 'max:190'],
            'nickname' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'parent_father' => ['nullable', 'string', 'max:190'],
            'parent_mother' => ['nullable', 'string', 'max:190'],
            'child_order' => ['nullable', 'string', 'max:50'],
            'instagram' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Peran ini tidak tersedia untuk jenis acara undangan ini.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role' => 'peran',
            'full_name' => 'nama lengkap',
            'nickname' => 'nama panggilan',
            'parent_father' => 'nama ayah',
            'parent_mother' => 'nama ibu',
            'child_order' => 'urutan anak',
        ];
    }

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        /** @var list<string> $roles */
        $roles = $this->invitation()->eventType->person_roles;

        return $roles;
    }

    protected function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
