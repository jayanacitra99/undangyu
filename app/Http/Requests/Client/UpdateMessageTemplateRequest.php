<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\MessageChannel;
use App\Models\MessageTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An edit to a template the client owns (26.2).
 *
 * A system default is refused by the policy rather than here: "you may not
 * edit this" is an authorization answer, not a validation one.
 */
class UpdateMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->template()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'channel' => ['nullable', Rule::enum(MessageChannel::class)],
            'subject' => ['nullable', 'string', 'max:190'],
            'body' => ['sometimes', 'required', 'string', 'max:'.StoreMessageTemplateRequest::MAX_BODY],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama templat', 'body' => 'isi pesan', 'subject' => 'subjek'];
    }

    protected function template(): MessageTemplate
    {
        $template = $this->route('template');

        abort_unless($template instanceof MessageTemplate, 404);

        return $template;
    }
}
