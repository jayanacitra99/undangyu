<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Guest;
use App\Models\Invitation;
use App\Services\Guests\GuestQuota;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One guest, added by hand (M5.1, 24.3).
 *
 * The quota lives here rather than in the controller because hard rule 10 says
 * so: a limit checked after the row is written is a limit the client has
 * already crossed.
 */
class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Guest::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return GuestRules::fields($this->invitation());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return GuestRules::ATTRIBUTES;
    }

    /**
     * The quota is about what is already stored, so it runs once the guest
     * itself is known to be valid — no point telling a client their list is
     * full about a row that was never going to save.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $quota = app(GuestQuota::class);
            $invitation = $this->invitation();

            if (! $quota->hasRoom($invitation)) {
                $validator->errors()->add('name', __(
                    'Kuota tamu paket ini sudah penuh (:limit tamu). Tingkatkan paket untuk menambah tamu.',
                    ['limit' => (string) $quota->limit($invitation)],
                ));
            }
        });
    }

    protected function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
