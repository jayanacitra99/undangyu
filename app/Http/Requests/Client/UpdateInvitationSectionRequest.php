<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\SectionKey;
use App\Models\InvitationSection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing a section: its title override, its visibility, and — for a custom
 * one — its body (M4.10, M4.11).
 */
class UpdateInvitationSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->section()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:190'],
            'is_visible' => ['sometimes', 'boolean'],
            'body' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * A body only means anything on a custom section; on a built-in one the
     * content is whatever the renderer puts there.
     *
     * @return array<string, mixed>
     */
    public function attributesForSection(): array
    {
        $attributes = array_intersect_key($this->validated(), array_flip(['title', 'is_visible']));

        if ($this->has('body') && $this->section()->section_key === SectionKey::Custom) {
            $attributes['content'] = [
                ...($this->section()->content ?? []),
                'body' => $this->validated('body'),
            ];
        }

        return $attributes;
    }

    private function section(): InvitationSection
    {
        $section = $this->route('section');

        abort_unless($section instanceof InvitationSection, 404);

        return $section;
    }
}
