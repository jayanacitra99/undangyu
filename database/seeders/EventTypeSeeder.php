<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PersonRole;
use App\Enums\SectionKey;
use App\Models\EventType;
use Illuminate\Database\Seeder;

/**
 * The eight event types the product ships with (playbook 5.2).
 *
 * `person_roles` decides which person slots the builder offers (M4.3), and
 * `default_sections` is the ordered set of blocks a new invitation of this type
 * starts with — a wedding tells a story and collects gifts, a corporate invite
 * does neither.
 *
 * Rows already present keep their values, so re-seeding never undoes an admin's
 * edit; only genuinely new types are added.
 */
class EventTypeSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, icon: string, person_roles: list<PersonRole>, default_sections: list<SectionKey>}>
     */
    public const TYPES = [
        [
            'name' => 'Pernikahan',
            'slug' => 'pernikahan',
            'icon' => 'bi-heart',
            'person_roles' => [PersonRole::Bride, PersonRole::Groom],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Quote,
                SectionKey::Persons,
                SectionKey::Events,
                SectionKey::Story,
                SectionKey::Gallery,
                SectionKey::Gifts,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
        [
            'name' => 'Tunangan',
            'slug' => 'tunangan',
            'icon' => 'bi-gem',
            'person_roles' => [PersonRole::Bride, PersonRole::Groom],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Quote,
                SectionKey::Persons,
                SectionKey::Events,
                SectionKey::Story,
                SectionKey::Gallery,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
        [
            'name' => 'Ulang Tahun',
            'slug' => 'ulang-tahun',
            'icon' => 'bi-balloon',
            'person_roles' => [PersonRole::Celebrant],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Persons,
                SectionKey::Events,
                SectionKey::Gallery,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
        [
            'name' => 'Aqiqah',
            'slug' => 'aqiqah',
            'icon' => 'bi-moon-stars',
            'person_roles' => [PersonRole::Child, PersonRole::Parents],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Quote,
                SectionKey::Persons,
                SectionKey::Events,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
        [
            'name' => 'Khitanan',
            'slug' => 'khitanan',
            'icon' => 'bi-stars',
            'person_roles' => [PersonRole::Child, PersonRole::Parents],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Quote,
                SectionKey::Persons,
                SectionKey::Events,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
        [
            'name' => 'Wisuda',
            'slug' => 'wisuda',
            'icon' => 'bi-mortarboard',
            'person_roles' => [PersonRole::Graduate],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Persons,
                SectionKey::Events,
                SectionKey::Gallery,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
        [
            'name' => 'Corporate',
            'slug' => 'corporate',
            'icon' => 'bi-building',
            'person_roles' => [PersonRole::Host],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Events,
                SectionKey::Gallery,
                SectionKey::Rsvp,
            ],
        ],
        [
            'name' => 'Umum',
            'slug' => 'umum',
            'icon' => 'bi-calendar-event',
            'person_roles' => [PersonRole::Host],
            'default_sections' => [
                SectionKey::Cover,
                SectionKey::Events,
                SectionKey::Rsvp,
                SectionKey::Guestbook,
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $index => $type) {
            EventType::query()->firstOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'],
                    'person_roles' => array_map(
                        static fn (PersonRole $role): string => $role->value,
                        $type['person_roles'],
                    ),
                    'default_sections' => array_map(
                        static fn (SectionKey $section): string => $section->value,
                        $type['default_sections'],
                    ),
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
