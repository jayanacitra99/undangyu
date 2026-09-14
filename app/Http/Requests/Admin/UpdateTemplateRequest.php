<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class UpdateTemplateRequest extends TemplateRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('template')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Keeping the current thumbnail is the normal case on edit.
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
