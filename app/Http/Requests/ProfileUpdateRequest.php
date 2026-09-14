<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * A user may edit their own profile and nothing else — the controller
     * writes to $request->user(), so there is no other record to reach.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The lengths match `users` after the identity migration narrowed them:
     * name varchar(150), email varchar(190). Breeze's stock max:255 let a
     * longer value validate and then fail at the column.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:190',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
