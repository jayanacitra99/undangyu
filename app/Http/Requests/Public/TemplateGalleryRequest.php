<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The three filters the public gallery accepts (M3.4).
 *
 * Unknown or malformed values are rejected rather than silently ignored, which
 * keeps the cache key space bounded — every accepted combination becomes a
 * cache entry.
 */
class TemplateGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['nullable', 'string', 'max:80', 'alpha_dash'],
            'category' => ['nullable', 'string', 'max:80', 'alpha_dash'],
            'tier' => ['nullable', 'string', 'in:free,premium'],
            'page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array{event_type: ?string, category: ?string, tier: ?string, page: int}
     */
    public function filters(): array
    {
        return [
            'event_type' => $this->validated('event_type'),
            'category' => $this->validated('category'),
            'tier' => $this->validated('tier'),
            'page' => (int) ($this->validated('page') ?? 1),
        ];
    }
}
