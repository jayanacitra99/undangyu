<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Services\Templates\ThemeConfigValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The "Tema" tab (19.4).
 *
 * There is no fixed rule set here: what is allowed is whatever the
 * invitation's template declares in `config_schema`. So the rules only check
 * the envelope, and ThemeConfigValidator checks the contents — key by key,
 * against that template — rejecting anything the schema does not declare.
 */
class UpdateThemeConfigRequest extends FormRequest
{
    /**
     * The config as the schema accepted it: only declared keys, values cast to
     * their declared type.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $checked = [];

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->invitation()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'theme_config' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $invitation = $this->invitation();
            $invitation->loadMissing('template');

            /** @var array<string, mixed> $submitted */
            $submitted = $this->input('theme_config', []);

            $result = app(ThemeConfigValidator::class)->validate($invitation->template, $submitted);

            foreach ($result['errors'] as $key => $message) {
                $validator->errors()->add($key, $message);
            }

            $this->checked = $result['config'];
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function themeConfig(): array
    {
        return $this->checked;
    }

    private function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
