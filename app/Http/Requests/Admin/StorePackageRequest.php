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
            // Required, not nullable: prepareForValidation already derived it
            // from the name, and Str::slug() of a name with no latin characters
            // is an empty string that must fail here rather than at the column.
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('packages', 'slug')],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $slug = $this->input('slug');
        $name = $this->input('name');

        $this->merge([
            // Only strings are slugged: a `slug[]` post must reach the rules as
            // an array and be rejected, not be cast to the word "Array".
            'slug' => is_string($slug) && $slug !== ''
                ? Str::slug($slug)
                : (is_string($name) ? Str::slug($name) : $name),
        ]);
    }

    protected function resolvedSlug(): string
    {
        return (string) $this->validated('slug');
    }
}
