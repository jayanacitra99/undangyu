<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Package;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePackageRequest extends PackageRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Package::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('packages', 'slug')],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
        ]);
    }

    protected function resolvedSlug(): string
    {
        return (string) $this->validated('slug');
    }
}
