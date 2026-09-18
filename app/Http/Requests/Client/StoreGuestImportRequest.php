<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\GuestImport;
use App\Models\Invitation;
use App\Services\Guests\GuestQuota;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An uploaded guest spreadsheet (25.3).
 *
 * The file is checked for what it is before it is stored; what is *in* it is
 * the job's problem, row by row. A quota that is already full is refused here
 * though: queueing a job that can only report "no room" is a worse answer than
 * saying so at the upload.
 */
class StoreGuestImportRequest extends FormRequest
{
    public const MAX_KILOBYTES = 5120;

    public function authorize(): bool
    {
        return $this->user()?->can('create', [GuestImport::class, $this->invitation()]) ?? false;
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
                // mimes, not mimetypes: a CSV written by Excel is sniffed as
                // text/plain, text/csv or application/csv depending on the
                // machine, and refusing a client's own export would be absurd.
                'mimes:csv,txt,xlsx,xls',
                'max:'.self::MAX_KILOBYTES,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Unggah berkas CSV atau XLSX.',
            'file.max' => 'Ukuran berkas maksimal :max KB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $quota = app(GuestQuota::class);
            $invitation = $this->invitation();

            if (! $quota->hasRoom($invitation)) {
                $validator->errors()->add('file', __(
                    'Kuota tamu paket ini sudah penuh (:limit tamu). Tingkatkan paket sebelum mengimpor.',
                    ['limit' => (string) $quota->limit($invitation)],
                ));
            }
        });
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
