<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingType;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One global setting (docs/03 § 3.9).
 *
 * Read settings through the Setting facade, not this model — the facade is what
 * caches them and casts `value` by `type`.
 *
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property SettingType $type
 * @property bool $is_public
 */
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    /**
     * The value cast by the row's own type.
     */
    public function typedValue(): mixed
    {
        return $this->type->cast($this->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_public' => 'boolean',
        ];
    }
}
