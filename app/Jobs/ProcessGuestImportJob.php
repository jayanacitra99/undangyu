<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Guests\GenerateGuestToken;
use App\Enums\GuestImportStatus;
use App\Imports\GuestRowsImport;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\GuestImport;
use App\Models\Invitation;
use App\Notifications\GuestImportCompleted;
use App\Services\Guests\GuestQuota;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Imports one uploaded guest spreadsheet (M5.3, docs/04 § 6).
 *
 * Partial success, always. A client's real list has a blank name at row 14 and
 * a duplicate at row 300, and refusing all 500 rows over two of them is how a
 * feature stops being used. Every row is judged on its own, and the failures
 * come back as a report naming row numbers.
 *
 * Rows are inserted in batches of 100 (docs/04 § 6): 500 individual INSERTs is
 * slow enough that clients refresh and upload the file a second time.
 */
class ProcessGuestImportJob implements ShouldQueue
{
    use Queueable;

    public const CHUNK = 100;

    /**
     * One attempt. A retry would re-read a file whose guests are already half
     * imported, and "some of them twice" is worse than "tell me what failed".
     */
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly int $importId) {}

    public function handle(GenerateGuestToken $generateToken, GuestQuota $quota): void
    {
        $import = GuestImport::query()->with(['invitation', 'user'])->find($this->importId);

        if ($import === null) {
            return;
        }

        $import->update(['status' => GuestImportStatus::Processing]);

        try {
            $rows = $this->read($import);
        } catch (Throwable $exception) {
            // A file we cannot parse is the client's problem to fix, not an
            // exception to leak: they get a readable reason, we get the log.
            Log::warning('Guest import could not be read', [
                'import_id' => $import->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            $this->finish($import, GuestImportStatus::Failed, 0, 0, [[
                'row' => 0,
                'name' => '',
                'message' => 'Berkas tidak bisa dibaca. Gunakan templat yang kami sediakan.',
            ]]);

            return;
        }

        $this->process($import, $rows, $generateToken, $quota);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function read(GuestImport $import): array
    {
        $reader = new GuestRowsImport;

        Excel::import($reader, $import->file_path, GuestImport::DISK);

        return $reader->rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function process(
        GuestImport $import,
        array $rows,
        GenerateGuestToken $generateToken,
        GuestQuota $quota,
    ): void {
        $invitation = $import->invitation;
        $errors = [];
        $pending = [];
        $imported = 0;
        $failed = 0;

        // Names already on the list, plus the names this file has used, so a
        // duplicate within the upload is caught as well as one against the
        // existing list.
        $seen = $this->existingNames($invitation);
        $tokens = [];
        $groups = $this->groups($invitation);

        $remaining = $quota->remaining($invitation);

        foreach ($rows as $index => $row) {
            $number = $index + GuestRowsImport::FIRST_DATA_ROW;
            $name = trim((string) ($row['nama'] ?? ''));

            if ($name === '') {
                $failed++;
                $this->addError($errors, $number, '', 'Nama wajib diisi.');

                continue;
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                $failed++;
                $this->addError($errors, $number, $name, 'Nama ini sudah ada di daftar tamu.');

                continue;
            }

            $phone = trim((string) ($row['telepon'] ?? ''));

            if ($phone !== '' && PhoneNumber::normalize($phone) === null) {
                $failed++;
                $this->addError($errors, $number, $name, 'Nomor telepon tidak bisa dibaca.');

                continue;
            }

            if ($remaining !== null && $remaining <= 0) {
                // Quota exhausted: stop cleanly rather than failing every
                // remaining row one at a time (docs/04 § 6).
                $this->addError($errors, $number, $name, 'Kuota tamu paket ini sudah penuh. Sisa baris tidak diimpor.');
                $failed += count($rows) - $index;

                break;
            }

            $token = $generateToken(
                $name,
                fn (string $candidate): bool => isset($tokens[$candidate])
                    || Guest::withTrashed()->where('token', $candidate)->exists(),
            );

            $tokens[$token] = true;
            $seen[$key] = true;

            $pending[] = $this->attributes($invitation, $row, $name, $phone, $token, $groups);

            if ($remaining !== null) {
                $remaining--;
            }

            if (count($pending) >= self::CHUNK) {
                $imported += $this->insert($pending);
                $pending = [];

                // The progress UI polls this row, so it has to move while the
                // job runs rather than only at the end.
                $import->update(['success_rows' => $imported, 'failed_rows' => $failed]);
            }
        }

        $imported += $this->insert($pending);

        $this->finish($import, GuestImportStatus::Completed, $imported, $failed, $errors);
    }

    /**
     * @param  array<int, array<string, mixed>>  $pending
     */
    private function insert(array $pending): int
    {
        if ($pending === []) {
            return 0;
        }

        try {
            Guest::query()->insert($pending);

            return count($pending);
        } catch (UniqueConstraintViolationException) {
            // A token this batch generated was taken between the check and the
            // insert. Retry the batch row by row, so one collision does not
            // cost the other ninety-nine.
            $inserted = 0;

            foreach ($pending as $attributes) {
                try {
                    Guest::query()->insert([$attributes]);
                    $inserted++;
                } catch (UniqueConstraintViolationException) {
                    // Nothing to do: the row is reported as failed by the
                    // caller's arithmetic.
                }
            }

            return $inserted;
        }
    }

    /**
     * One row's columns, ready for a batch insert. `insert()` bypasses the
     * model, so the timestamps are set here.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $groups
     * @return array<string, mixed>
     */
    private function attributes(
        Invitation $invitation,
        array $row,
        string $name,
        string $phone,
        string $token,
        array &$groups,
    ): array {
        $now = now();

        return [
            'invitation_id' => $invitation->getKey(),
            'guest_group_id' => $this->groupId($invitation, $row, $groups),
            'title' => $this->title($row),
            'name' => mb_substr($name, 0, 190),
            'phone' => $phone === '' ? null : PhoneNumber::normalize($phone),
            'email' => $this->text($row, 'email', 190),
            'address' => $this->text($row, 'alamat', 500),
            'token' => $token,
            'max_pax' => $this->pax($row),
            'is_vip' => $this->flag($row),
            'table_number' => $this->text($row, 'meja', 20),
            'notes' => $this->text($row, 'catatan', 500),
            'open_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * A group named in the spreadsheet is created if the invitation does not
     * have it yet: a client who typed "Kantor" in 40 rows meant the group, and
     * making them create it first would be a rule they learn by failing.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $groups
     */
    private function groupId(Invitation $invitation, array $row, array &$groups): ?int
    {
        $name = trim((string) ($row['grup'] ?? ''));

        if ($name === '') {
            return null;
        }

        $key = mb_strtolower($name);

        if (! isset($groups[$key])) {
            $group = $invitation->guestGroups()->create([
                'name' => mb_substr($name, 0, 100),
                'sort_order' => count($groups),
            ]);

            $groups[$key] = (int) $group->getKey();
        }

        return $groups[$key];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function title(array $row): ?string
    {
        $title = trim((string) ($row['sebutan'] ?? ''));

        if ($title === '') {
            return null;
        }

        // Matched case-insensitively against the known honorifics; anything
        // else is kept as typed, truncated to the column.
        foreach (Guest::TITLES as $known) {
            if (mb_strtolower($known) === mb_strtolower($title)) {
                return $known;
            }
        }

        return mb_substr($title, 0, 30);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function pax(array $row): int
    {
        $pax = (int) ($row['jumlah_orang'] ?? 2);

        return max(1, min(20, $pax === 0 ? 2 : $pax));
    }

    /**
     * "ya", "iya", "y", "1", "true" — the ways a client says yes in a
     * spreadsheet column labelled vip.
     *
     * @param  array<string, mixed>  $row
     */
    private function flag(array $row): bool
    {
        $value = mb_strtolower(trim((string) ($row['vip'] ?? '')));

        return in_array($value, ['ya', 'iya', 'y', 'yes', '1', 'true', 'vip'], true);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function text(array $row, string $key, int $limit): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    /**
     * @return array<string, true>
     */
    private function existingNames(Invitation $invitation): array
    {
        $names = [];

        $invitation->guests()
            ->select('name')
            ->cursor()
            ->each(function (Guest $guest) use (&$names): void {
                $names[mb_strtolower($guest->name)] = true;
            });

        return $names;
    }

    /**
     * @return array<string, int>
     */
    private function groups(Invitation $invitation): array
    {
        return $invitation->guestGroups()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (GuestGroup $group): array => [mb_strtolower($group->name) => (int) $group->getKey()])
            ->all();
    }

    /**
     * @param  list<array{row: int, name: string, message: string}>  $errors
     */
    private function addError(array &$errors, int $row, string $name, string $message): void
    {
        if (count($errors) >= GuestImport::MAX_ERRORS) {
            return;
        }

        $errors[] = ['row' => $row, 'name' => $name, 'message' => $message];
    }

    /**
     * @param  list<array{row: int, name: string, message: string}>  $errors
     */
    private function finish(
        GuestImport $import,
        GuestImportStatus $status,
        int $imported,
        int $failed,
        array $errors,
    ): void {
        $import->update([
            'status' => $status,
            'total_rows' => $imported + $failed,
            'success_rows' => $imported,
            'failed_rows' => $failed,
            'errors' => $errors === [] ? null : $errors,
        ]);

        $import->user?->notify(new GuestImportCompleted($import));
    }
}
