<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationStory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One entry on the story timeline (M4.5).
 */
class StoreInvitationStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [InvitationStory::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            // A plain date, not a datetime: the column is a date and the
            // memory has no clock.
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'date' => 'tanggal',
            'description' => 'cerita',
        ];
    }

    protected function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
