<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Template;

class StoreTemplateRequest extends TemplateRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Template::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'thumbnail' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
