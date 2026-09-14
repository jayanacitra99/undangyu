<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Account deletion is the most consequential write a client can make, so its
 * password re-check lives here rather than inline in the controller.
 */
class DeleteProfileRequest extends FormRequest
{
    /**
     * Breeze's delete form renders its errors from the `userDeletion` bag.
     */
    protected $errorBag = 'userDeletion';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
