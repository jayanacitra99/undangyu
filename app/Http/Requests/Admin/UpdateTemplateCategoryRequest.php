<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\TemplateCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTemplateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TemplateCategory|null $templateCategory */
        $templateCategory = $this->route('template_category');

        return $templateCategory !== null && ($this->user()?->can('update', $templateCategory) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('template_categories', 'slug')->ignore($this->route('template_category'))],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'slug.unique' => 'Slug sudah dipakai kategori lain.',
            'slug.alpha_dash' => 'Slug hanya boleh huruf, angka dan tanda hubung.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
