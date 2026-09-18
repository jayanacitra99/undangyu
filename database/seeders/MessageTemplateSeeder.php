<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MessageChannel;
use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * The default Indonesian texts every invitation starts from (26.1).
 *
 * Written the way a couple actually writes them: the honorific, the two names,
 * the date, the link, and a closing line. A client who never opens the editor
 * still sends something they would have written themselves.
 *
 * updateOrCreate on the name, so re-seeding a deployment corrects the wording
 * of the defaults without touching a single client's copy of them.
 */
class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            MessageTemplate::query()->updateOrCreate(
                [
                    'name' => $template['name'],
                    'is_system' => true,
                    'invitation_id' => null,
                    'user_id' => null,
                ],
                [
                    'channel' => MessageChannel::Whatsapp->value,
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, body: string, variables: list<string>}>
     */
    private function templates(): array
    {
        return [
            [
                'name' => 'Undangan',
                'body' => <<<'TEXT'
                Kepada Yth. {guest_name}

                Tanpa mengurangi rasa hormat, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di acara pernikahan kami:

                *{couple_names}*
                {event_date}, pukul {event_time}
                {venue}

                Informasi lengkap dan konfirmasi kehadiran ada di tautan berikut:
                {invitation_url}

                Merupakan suatu kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.

                Terima kasih.
                TEXT,
                'variables' => ['guest_name', 'couple_names', 'event_date', 'event_time', 'venue', 'invitation_url'],
            ],
            [
                'name' => 'Pengingat',
                'body' => <<<'TEXT'
                Halo {guest_name}, semoga sehat selalu.

                Mengingatkan kembali acara pernikahan *{couple_names}* pada {event_date} pukul {event_time} di {venue}.

                Undangan dan konfirmasi kehadiran:
                {invitation_url}

                Sampai jumpa di hari bahagia kami.
                TEXT,
                'variables' => ['guest_name', 'couple_names', 'event_date', 'event_time', 'venue', 'invitation_url'],
            ],
            [
                'name' => 'Terima kasih',
                'body' => <<<'TEXT'
                {guest_name}, terima kasih banyak atas kehadiran dan doa restunya di acara pernikahan kami.

                Kebahagiaan kami menjadi lengkap karena Anda ada di sana.

                Salam hangat,
                {couple_names}
                TEXT,
                'variables' => ['guest_name', 'couple_names'],
            ],
        ];
    }
}
