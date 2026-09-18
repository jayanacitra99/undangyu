<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuestImportStatus;
use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\GuestImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One upload of a guest spreadsheet (M5.3, docs/03 § 3.5).
 *
 * `errors` is a list of {row, name, message}: the row number as the client
 * sees it in their spreadsheet, so "row 214" means the line they can go and
 * look at rather than an offset into our parse.
 *
 * @property int $invitation_id
 * @property int $user_id
 * @property string $file_path
 * @property string $original_filename
 * @property int $total_rows
 * @property int $success_rows
 * @property int $failed_rows
 * @property list<array{row: int, name: string, message: string}>|null $errors
 * @property GuestImportStatus $status
 */
class GuestImport extends Model implements BelongsToInvitation
{
    /** @use HasFactory<GuestImportFactory> */
    use HasFactory;

    use PartOfInvitation;

    /**
     * The private disk: an uploaded guest list is names, phone numbers and
     * addresses. It is never served, only read by the job.
     */
    public const DISK = 'local';

    public const DIRECTORY = 'guest-imports';

    /**
     * How many errors are kept. A file where every row is wrong is a file the
     * client should fix and re-upload, not a 5000-entry JSON column — the
     * first 200 say what is wrong with it.
     */
    public const MAX_ERRORS = 200;

    protected $fillable = [
        'invitation_id',
        'user_id',
        'file_path',
        'original_filename',
        'total_rows',
        'success_rows',
        'failed_rows',
        'errors',
        'status',
    ];

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GuestImportStatus::class,
            'errors' => 'array',
            'total_rows' => 'integer',
            'success_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }
}
