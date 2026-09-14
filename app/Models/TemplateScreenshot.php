<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TemplateScreenshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One image on a template's detail page (docs/03 § 3.3).
 *
 * @property int $template_id
 * @property string $path
 * @property string|null $caption
 * @property int $sort_order
 */
class TemplateScreenshot extends Model
{
    /** @use HasFactory<TemplateScreenshotFactory> */
    use HasFactory;

    protected $fillable = [
        'template_id',
        'path',
        'caption',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
