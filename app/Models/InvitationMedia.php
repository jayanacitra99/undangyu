<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\InvitationMediaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One image, video or audio track on an invitation (docs/03 § 3.4).
 *
 * @property int $invitation_id
 * @property MediaType $type
 * @property MediaSource $source
 * @property string|null $disk
 * @property string|null $path
 * @property string|null $embed_url
 * @property string|null $thumbnail
 * @property array<string, string>|null $conversions
 * @property string|null $caption
 * @property int|null $file_size
 * @property bool $is_cover
 * @property int $sort_order
 */
class InvitationMedia extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationMediaFactory> */
    use HasFactory;

    use PartOfInvitation;

    /**
     * Laravel would pluralise this to `invitation_medias`.
     */
    protected $table = 'invitation_media';

    /**
     * Uploads land on the private disk (18.2). A draft invitation's photos
     * are the client's until they publish, and `storage/app/public` is a
     * guessable path — so nothing reaches a browser except through
     * MediaController, which asks the policy first.
     */
    public const UPLOAD_DISK = 'local';

    /**
     * The renditions ProcessMediaJob writes, longest edge in pixels. `full` is
     * the cap 18.1 compresses to client-side; it exists here too because a
     * client with scripting off, or an upload from the API, still must not put
     * a 6000px original in front of a guest on mobile data.
     *
     * @var array<string, int>
     */
    public const CONVERSIONS = [
        'thumb' => 400,
        'medium' => 1000,
        'full' => 2000,
    ];

    protected $fillable = [
        'invitation_id',
        'type',
        'source',
        'disk',
        'path',
        'embed_url',
        'thumbnail',
        'conversions',
        'caption',
        'file_size',
        'is_cover',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * Where the renderer points: an embed keeps its own URL, an upload is
     * resolved against its disk.
     */
    public function url(): ?string
    {
        if ($this->source === MediaSource::Embed) {
            return $this->embed_url;
        }

        if ($this->path === null) {
            return null;
        }

        return $this->isPrivate()
            ? route('media.show', ['media' => $this->getKey()])
            : Storage::disk($this->disk ?? 'public')->url($this->path);
    }

    /**
     * A row on the private disk has no public path, so its URL is the route
     * that streams it. Library audio and the seeded demo rows sit on the
     * public disk and keep their direct URL.
     */
    public function isPrivate(): bool
    {
        return ($this->disk ?? 'public') === self::UPLOAD_DISK;
    }

    /**
     * One named conversion — `thumb`, `medium`, `webp`, `og` — falling back to
     * the original when the queue has not made it yet.
     */
    public function conversion(string $name): ?string
    {
        $path = $this->conversions[$name] ?? null;

        if ($path === null) {
            return $this->url();
        }

        return $this->isPrivate()
            ? route('media.show', ['media' => $this->getKey(), 'variant' => $name])
            : Storage::disk($this->disk ?? 'public')->url($path);
    }

    /**
     * Every file this row owns — the original and whatever the queue made of
     * it — for deleting them together.
     *
     * @return list<string>
     */
    public function storedPaths(): array
    {
        $paths = array_values(array_filter([
            $this->path,
            $this->thumbnail,
            ...array_values($this->conversions ?? []),
        ]));

        return array_values(array_unique(array_filter(
            $paths,
            // A thumbnail can be a remote URL (an embed's poster frame), which
            // is not ours to delete.
            fn (string $path): bool => ! str_starts_with($path, 'http'),
        )));
    }

    /**
     * @param  Builder<InvitationMedia>  $query
     */
    public function scopeOfType(Builder $query, MediaType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'source' => MediaSource::class,
            'conversions' => 'array',
            'file_size' => 'integer',
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
