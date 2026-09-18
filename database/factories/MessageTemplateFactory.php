<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageChannel;
use App\Models\Invitation;
use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    protected $model = MessageTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'invitation_id' => Invitation::factory(),
            'name' => 'Undangan utama',
            'channel' => MessageChannel::Whatsapp->value,
            'subject' => null,
            'body' => "Kepada Yth. {guest_name},\n\nKami mengundang Anda ke pernikahan {couple_names} pada {event_date}.\n\n{invitation_url}",
            'variables' => null,
            'is_system' => false,
        ];
    }

    /**
     * A seeded default: no owner, no invitation, not editable.
     */
    public function system(): self
    {
        return $this->state(fn (): array => [
            'invitation_id' => null,
            'user_id' => null,
            'is_system' => true,
        ]);
    }
}
