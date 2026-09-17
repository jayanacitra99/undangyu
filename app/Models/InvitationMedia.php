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

        return Storage::disk($this->disk ?? 'public')->url($this->path);
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

        return Storage::disk($this->disk ?? 'public')->url($path);
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
