<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * The token-consuming half of a password reset.
 *
 * Breeze validated the token as `['required']` only, so an array token reached
 * the broker's hash comparison and raised a TypeError instead of failing.
 */
class NewPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
