<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PersonRole;
use App\Enums\SectionKey;
use App\Models\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEventTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EventType::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('event_types', 'slug')],
            'icon' => ['nullable', 'string', 'max:60'],
            'person_roles' => ['required', 'array', 'min:1'],
            'person_roles.*' => ['required', Rule::enum(PersonRole::class)],
            'default_sections' => ['required', 'array', 'min:1'],
            'default_sections.*' => ['required', Rule::enum(SectionKey::class)],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah dipakai jenis acara lain.',
            'slug.alpha_dash' => 'Slug hanya boleh huruf, angka dan tanda hubung.',
            'person_roles.required' => 'Pilih minimal satu peran.',
            'person_roles.min' => 'Pilih minimal satu peran.',
            'default_sections.required' => 'Pilih minimal satu seksi bawaan.',
            'default_sections.min' => 'Pilih minimal satu seksi bawaan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // An admin who leaves the slug blank means "derive it from the name".
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
