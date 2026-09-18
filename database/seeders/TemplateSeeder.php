<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TemplateStatus;
use App\Models\EventType;
use App\Models\Template;
use App\Models\TemplateCategory;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Seeder;

/**
 * Three templates so the catalog is never empty on a fresh install
 * (playbook 6.6).
 *
 * `view_key` is the folder under resources/js/invitation/templates/, so a row
 * may only be published once that folder exists — otherwise the renderer
 * falls back and a buyer gets a template that is not the one they picked.
 * `BUILT` is that list; everything else stays a draft.
 *
 * Idempotent on `view_key`, like the rest of the catalog seeders.
 */
class TemplateSeeder extends Seeder
{
    /**
     * @var list<array{name: string, view_key: string, category: string, description: string, event_types: list<string>, is_premium: bool, extra_price: int, screenshots: list<string>}>
     */
    public const TEMPLATES = [
        [
            'name' => 'Sekar Ayu',
            'view_key' => 'sekar-ayu',
            'category' => 'floral',
            'description' => 'Rangkaian bunga cat air dengan tipografi klasik. Cocok untuk akad dan resepsi.',
            'event_types' => ['pernikahan', 'tunangan'],
            'is_premium' => false,
            'extra_price' => 0,
            'screenshots' => ['Sampul', 'Mempelai', 'Galeri'],
        ],
        [
            'name' => 'Lentera Malam',
            'view_key' => 'lentera-malam',
            'category' => 'dark',
            'description' => 'Latar gelap dengan aksen emas dan animasi halus.',
            'event_types' => ['pernikahan', 'ulang-tahun', 'corporate'],
            'is_premium' => true,
            'extra_price' => 150000,
            'screenshots' => ['Sampul', 'Rangkaian acara', 'Buku tamu'],
        ],
        [
            'name' => 'Ruang Putih',
            'view_key' => 'ruang-putih',
            'category' => 'minimalis',
            'description' => 'Banyak ruang kosong, satu warna aksen, fokus pada isi undangan.',
            'event_types' => ['pernikahan', 'aqiqah', 'khitanan', 'wisuda', 'umum'],
            'is_premium' => false,
            'extra_price' => 0,
            'screenshots' => ['Sampul', 'Lokasi acara'],
        ],
    ];

    /**
     * "Sekar Ayu"'s own schema (22.7).
     *
     * This is the contract between the template and the "Tema" tab: the tab
     * renders a control per key and ThemeConfigValidator refuses anything not
     * declared here, so a key added to the component has to be added here too
     * before a client can set it.
     *
     * The colour defaults are the template's actual palette — warm sand and
     * cream — not placeholders, because `default_config` is what every
     * invitation renders with until a client changes something.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function sekarAyuSchema(): array
    {
        return [
            'colors' => [
                'primary' => ['type' => 'color', 'default' => '#8B7355', 'label' => 'Warna utama'],
                'secondary' => ['type' => 'color', 'default' => '#D4C5B0', 'label' => 'Warna aksen'],
                'text' => ['type' => 'color', 'default' => '#3A3A3A', 'label' => 'Warna teks'],
                'surface' => ['type' => 'color', 'default' => '#FBF8F4', 'label' => 'Warna latar'],
            ],
            'fonts' => [
                'heading' => [
                    'type' => 'select',
                    'options' => ['Playfair Display', 'Cormorant Garamond', 'Marcellus'],
                    'default' => 'Playfair Display',
                    'label' => 'Font judul',
                ],
                'body' => [
                    'type' => 'select',
                    'options' => ['Lato', 'Montserrat', 'Inter'],
                    'default' => 'Lato',
                    'label' => 'Font isi',
                ],
            ],
            'options' => [
                'show_countdown' => ['type' => 'boolean', 'default' => true, 'label' => 'Tampilkan hitung mundur'],
                'show_music_button' => ['type' => 'boolean', 'default' => true, 'label' => 'Tampilkan tombol musik'],
            ],
        ];
    }

    /**
     * Which templates have a component folder under
     * resources/js/invitation/templates/, and may therefore be published.
     *
     * @var list<string>
     */
    public const BUILT = ['sekar-ayu'];

    public function run(): void
    {
        $categories = TemplateCategory::query()->pluck('id', 'slug');
        $eventTypes = EventType::query()->pluck('id', 'slug');

        foreach (self::TEMPLATES as $index => $definition) {
            $categoryId = $categories[$definition['category']] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $isBuilt = in_array($definition['view_key'], self::BUILT, true);

            // A template with a component folder gets its own schema; the rest
            // keep the example one until they are built.
            $schema = $isBuilt ? self::sekarAyuSchema() : TemplateFactory::exampleConfigSchema();

            $template = Template::query()->firstOrCreate(
                ['view_key' => $definition['view_key']],
                [
                    'template_category_id' => $categoryId,
                    'name' => $definition['name'],
                    'slug' => $definition['view_key'],
                    'description' => $definition['description'],
                    'thumbnail' => 'templates/thumbnails/'.$definition['view_key'].'.webp',
                    'version' => '1.0.0',
                    'config_schema' => $schema,
                    'default_config' => TemplateFactory::defaultsFor($schema),
                    'demo_data' => [
                        'couple' => ['bride' => 'Ayu Lestari', 'groom' => 'Bagus Prakoso'],
                        'venue' => 'Gedung Serbaguna Puri Agung, Denpasar',
                    ],
                    'is_premium' => $definition['is_premium'],
                    'extra_price' => $definition['extra_price'],
                    'status' => $isBuilt ? TemplateStatus::Published : TemplateStatus::Draft,
                    'sort_order' => $index,
                ],
            );

            /*
            | A row seeded before its folder existed is still a draft with the
            | example schema. Bring it up to date — but only while it is still
            | a draft, so an admin who deliberately archived a template does
            | not find it republished by a re-seed.
            */
            if ($isBuilt && $template->status === TemplateStatus::Draft) {
                $template->update([
                    'config_schema' => $schema,
                    'default_config' => TemplateFactory::defaultsFor($schema),
                    'status' => TemplateStatus::Published,
                ]);
            }

            $template->eventTypes()->syncWithoutDetaching(
                $eventTypes->only($definition['event_types'])->values()->all(),
            );

            if ($template->screenshots()->exists()) {
                continue;
            }

            foreach ($definition['screenshots'] as $position => $caption) {
                $template->screenshots()->create([
                    'path' => 'templates/screenshots/'.$definition['view_key'].'-'.($position + 1).'.webp',
                    'caption' => $caption,
                    'sort_order' => $position,
                ]);
            }
        }
    }
}
