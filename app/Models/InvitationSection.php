<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SectionKey;
use Database\Factories\InvitationSectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One configurable block of an invitation (docs/03 § 3.4).
 *
 * @property int $invitation_id
 * @property SectionKey $section_key
 * @property string|null $title
 * @property array<string, mixed>|null $content
 * @property bool $is_visible
 * @property int $sort_order
 */
class InvitationSection extends Model
{
    /** @use HasFactory<InvitationSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'section_key',
        'title',
        'content',
        'is_visible',
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
     * What the public renderer draws. A hidden section still exists — the
     * client is switching it off, not deleting their content.
     *
     * @param  Builder<InvitationSection>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * The heading to draw: the client's override, else the section's own name.
     */
    public function heading(): string
    {
        return $this->title ?? $this->section_key->label();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_key' => SectionKey::class,
            'content' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
