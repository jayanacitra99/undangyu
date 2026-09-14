<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Order;
use App\Models\Package;
use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The checkout POST: one package, optionally one template (M2.4).
 *
 * Slugs rather than ids, because that is what the pricing page and the gallery
 * link with. Both are resolved here so the controller never queries on
 * unvalidated input.
 */
class StoreOrderRequest extends FormRequest
{
    private ?Package $resolvedPackage = null;

    private ?Template $resolvedTemplate = null;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package' => [
                'required', 'string', 'max:120', 'alpha_dash',
                Rule::exists('packages', 'slug')->where('is_active', true),
            ],
            'template' => [
                'nullable', 'string', 'max:140', 'alpha_dash',
                Rule::exists('templates', 'slug')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * A template that exists but is not published is not on sale, and neither
     * is one that does not serve the package's event types. The first is
     * checked here; the second waits for M4, where an order learns which event
     * type it is for.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->input('template') === null) {
                return;
            }

            if ($this->template() === null) {
                $validator->errors()->add('template', 'Template ini belum terbit.');
            }
        });
    }

    public function package(): Package
    {
        return $this->resolvedPackage ??= Package::query()
            ->active()
            ->where('slug', $this->validated('package'))
            ->sole();
    }

    public function template(): ?Template
    {
        $slug = $this->validated('template');

        if ($slug === null || $slug === '') {
            return null;
        }

        return $this->resolvedTemplate ??= Template::query()
            ->published()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package.required' => 'Pilih paket terlebih dahulu.',
            'package.exists' => 'Paket tidak tersedia.',
            'template.exists' => 'Template tidak tersedia.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $template = $this->input('template');

        $this->merge([
            'template' => $template === '' ? null : $template,
        ]);
    }
}
