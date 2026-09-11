<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Propaganistas\LaravelPhone\PhoneNumber;

class RegisterRequest extends FormRequest
{
    /**
     * The default region for phone numbers typed without a country code.
     * M1.1: WhatsApp is the primary channel and the market is Indonesian.
     */
    public const DEFAULT_REGION = 'ID';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190', Rule::unique(User::class)],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::phone()->country(self::DEFAULT_REGION)->mobile(),
                Rule::unique(User::class),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.phone' => 'Masukkan nomor WhatsApp Indonesia yang valid, contoh: 0812-3456-7890.',
        ];
    }

    /**
     * Normalise the phone number to E.164 before validation, so `unique` compares
     * the same shape that gets stored — 08123456789 and +628123456789 are one number.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (! is_string($phone) || trim($phone) === '') {
            return;
        }

        try {
            $this->merge([
                'phone' => (new PhoneNumber($phone, self::DEFAULT_REGION))->formatE164(),
            ]);
        } catch (\Throwable) {
            // Leave it as typed — the `phone` rule below reports the real problem.
        }
    }
}
