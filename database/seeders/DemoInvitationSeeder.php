<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GiftType;
use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Enums\PersonRole;
use App\Enums\UserStatus;
use App\Models\EventType;
use App\Models\Invitation;
use App\Models\Package;
use App\Models\Template;
use App\Models\User;
use App\Services\Entitlements\EntitlementResolver;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * One complete published invitation (playbook 14.7).
 *
 * This is what template previews render against in Session 21, so it fills
 * every section a wedding template can draw: two people, akad and resepsi, a
 * story, a gallery, music and three ways to send a gift.
 *
 * Idempotent on the slug, like the rest of the seeders.
 */
class DemoInvitationSeeder extends Seeder
{
    public const SLUG = 'ayu-bagus';

    public const DEMO_EMAIL = 'demo@undangyu.com';

    public function run(): void
    {
        $eventType = EventType::query()->where('slug', 'pernikahan')->first();
        $template = Template::query()->orderBy('id')->first();
        $package = Package::query()->where('slug', 'premium')->first();

        if ($eventType === null || $template === null || $package === null) {
            $this->command?->warn('DemoInvitationSeeder dilewati: butuh event type, template dan paket.');

            return;
        }

        $owner = $this->owner();

        $invitation = Invitation::query()->firstOrCreate(
            ['slug' => self::SLUG],
            [
                'user_id' => $owner->getKey(),
                'event_type_id' => $eventType->getKey(),
                'template_id' => $template->getKey(),
                'template_version' => $template->version,
                'package_id' => $package->getKey(),
                'title' => 'Ayu & Bagus',
                'status' => InvitationStatus::Published,
                'visibility' => InvitationVisibility::Public,
                'language' => 'id',
                'timezone' => 'Asia/Jakarta',
                'theme_config' => $template->default_config,
                'settings' => InvitationFactory::defaultSettings(),
                'entitlements' => app(EntitlementResolver::class)->resolve($package),
                'meta_title' => 'Ayu & Bagus — 12 Desember 2026',
                'meta_description' => 'Dengan memohon rahmat Tuhan Yang Maha Esa, kami mengundang Anda ke pernikahan kami di Surabaya.',
                'published_at' => now()->subWeek(),
                'expires_at' => now()->addYear(),
            ],
        );

        // Everything below is keyed so a re-seed refreshes the demo rather than
        // doubling it.
        $this->seedSections($invitation, $eventType);
        $this->seedPersons($invitation);
        $this->seedEvents($invitation);
        $this->seedStories($invitation);
        $this->seedMedia($invitation);
        $this->seedGifts($invitation);

        $this->command?->info("Demo invitation ready: /{$invitation->slug}");
    }

    private function owner(): User
    {
        $owner = User::query()->firstOrNew(['email' => self::DEMO_EMAIL]);

        if (! $owner->exists) {
            $owner->fill([
                'name' => 'Ayu Lestari',
                'phone' => '+628123456780',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ])->save();

            $owner->assignRole('client');
        }

        return $owner;
    }

    private function seedSections(Invitation $invitation, EventType $eventType): void
    {
        /** @var list<string> $keys */
        $keys = $eventType->default_sections;

        foreach ($keys as $index => $key) {
            $invitation->sections()->updateOrCreate(
                ['section_key' => $key, 'sort_order' => $index],
                ['is_visible' => true],
            );
        }
    }

    private function seedPersons(Invitation $invitation): void
    {
        $people = [
            [
                'role' => PersonRole::Bride->value,
                'full_name' => 'Ayu Lestari, S.Kom.',
                'nickname' => 'Ayu',
                'parent_father' => 'Bapak Suryanto',
                'parent_mother' => 'Ibu Sri Rahayu',
                'child_order' => 'Putri pertama dari dua bersaudara',
                'instagram' => '@ayulestari',
                'sort_order' => 0,
            ],
            [
                'role' => PersonRole::Groom->value,
                'full_name' => 'Bagus Prakoso, S.T.',
                'nickname' => 'Bagus',
                'parent_father' => 'Bapak Hartono',
                'parent_mother' => 'Ibu Endang Susanti',
                'child_order' => 'Putra kedua dari tiga bersaudara',
                'instagram' => '@bagusprakoso',
                'sort_order' => 1,
            ],
        ];

        foreach ($people as $person) {
            $invitation->persons()->updateOrCreate(
                ['role' => $person['role']],
                $person,
            );
        }
    }

    private function seedEvents(Invitation $invitation): void
    {
        // 12 December 2026, a Saturday. Stored UTC: 02:00 UTC is 09:00 WIB.
        $day = Carbon::parse('2026-12-12', 'UTC');

        $events = [
            [
                'name' => 'Akad Nikah',
                'start_at' => $day->copy()->setTime(2, 0),
                'end_at' => $day->copy()->setTime(4, 0),
                'venue_name' => 'Masjid Al-Akbar Surabaya',
                'address' => 'Jl. Masjid Al-Akbar Timur No. 1, Pagesangan, Surabaya',
                'dress_code' => 'Busana muslim',
                'sort_order' => 0,
            ],
            [
                'name' => 'Resepsi',
                'start_at' => $day->copy()->setTime(4, 0),
                'end_at' => $day->copy()->setTime(9, 0),
                'venue_name' => 'Hotel Majapahit',
                'address' => 'Jl. Tunjungan No. 65, Genteng, Surabaya, Jawa Timur',
                'dress_code' => 'Batik atau earth tone',
                'sort_order' => 1,
            ],
        ];

        foreach ($events as $event) {
            $invitation->events()->updateOrCreate(
                ['name' => $event['name']],
                $event + [
                    'is_all_day' => false,
                    'maps_url' => 'https://maps.app.goo.gl/demoundangyu',
                    'latitude' => -7.2575,
                    'longitude' => 112.7521,
                ],
            );
        }
    }

    private function seedStories(Invitation $invitation): void
    {
        $stories = [
            ['2019-08-17', 'Pertama Bertemu', 'Dipertemukan di acara kampus, lalu ngobrol sampai kantin tutup.', 0],
            ['2021-02-14', 'Mulai Dekat', 'Dari teman belajar jadi teman cerita setiap hari.', 1],
            ['2024-06-01', 'Lamaran', 'Keluarga bertemu, doa dipanjatkan, tanggal ditentukan.', 2],
        ];

        foreach ($stories as [$date, $title, $description, $order]) {
            $invitation->stories()->updateOrCreate(
                ['title' => $title],
                [
                    'date' => $date,
                    'description' => $description,
                    'sort_order' => $order,
                ],
            );
        }
    }

    private function seedMedia(Invitation $invitation): void
    {
        foreach (range(1, 6) as $index) {
            $invitation->media()->updateOrCreate(
                ['path' => "invitations/demo/gallery-{$index}.webp"],
                [
                    'type' => MediaType::Image->value,
                    'source' => MediaSource::Upload->value,
                    'disk' => 'public',
                    'thumbnail' => "invitations/demo/gallery-{$index}-thumb.webp",
                    'conversions' => [
                        'thumb' => "invitations/demo/gallery-{$index}-thumb.webp",
                        'medium' => "invitations/demo/gallery-{$index}-medium.webp",
                    ],
                    'caption' => $index === 1 ? 'Prewedding di Bromo' : null,
                    'file_size' => 640_000 + ($index * 12_000),
                    'is_cover' => $index === 1,
                    'sort_order' => $index - 1,
                ],
            );
        }

        $invitation->media()->updateOrCreate(
            ['path' => 'library/audio/akad-instrumental.mp3'],
            [
                'type' => MediaType::Audio->value,
                'source' => MediaSource::Library->value,
                'disk' => 'public',
                'caption' => 'Instrumental akustik',
                'file_size' => 3_800_000,
                'is_cover' => false,
                'sort_order' => 10,
            ],
        );
    }

    private function seedGifts(Invitation $invitation): void
    {
        $gifts = [
            [
                'type' => GiftType::Bank->value,
                'provider_name' => 'BCA',
                'account_name' => 'Ayu Lestari',
                'account_number' => '1234567890',
                'sort_order' => 0,
            ],
            [
                'type' => GiftType::Ewallet->value,
                'provider_name' => 'GoPay',
                'account_name' => 'Bagus Prakoso',
                'account_number' => '+628123456781',
                'sort_order' => 1,
            ],
            [
                'type' => GiftType::Address->value,
                'recipient_name' => 'Ayu Lestari',
                'address' => 'Perumahan Griya Asri Blok C2 No. 14, Sidoarjo, Jawa Timur 61256',
                'notes' => 'Mohon konfirmasi sebelum mengirim.',
                'sort_order' => 2,
            ],
        ];

        foreach ($gifts as $gift) {
            $invitation->gifts()->updateOrCreate(
                ['type' => $gift['type']],
                $gift,
            );
        }
    }
}
