<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One message template, as the editor reads it (M8.2).
 *
 * `is_editable` rather than leaving the client to infer it from `is_system`:
 * what the UI needs to know is whether to disable the form, and that is a
 * question the model already answers.
 *
 * @mixin MessageTemplate
 */
class MessageTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'subject' => $this->subject,
            'body' => $this->body,
            'is_system' => $this->is_system,
            'is_editable' => $this->isEditable(),
            'scope' => $this->invitation_id !== null
                ? 'invitation'
                : ($this->user_id !== null ? 'user' : 'system'),
        ];
    }
}
