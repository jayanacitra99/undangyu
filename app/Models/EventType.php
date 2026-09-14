<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A kind of event an invitation can be built for (docs/03 § 3.3).
 *
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property list<string> $person_roles
 * @property list<string> $default_sections
 * @property bool $is_active
 * @property int $sort_order
 */
class EventType extends Model
{
    /** @use HasFactory<EventTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'person_roles',
        'default_sections',
        'is_active',
        'sort_order',
    ];

    /**
     * Only the types a client may pick. Anything the admin has switched off is
     * gone from every public and client-facing listing.
     *
     * @param  Builder<EventType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<EventType>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'person_roles' => 'array',
            'default_sections' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
