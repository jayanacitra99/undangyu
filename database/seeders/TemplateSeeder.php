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
 * Three draft templates so the catalog is never empty on a fresh install
 * (playbook 6.6).
 *
 * They are drafts on purpose: the Vue component folders under
 * resources/js/invitation/templates/ do not exist yet, so nothing here may be
 * reachable from the public gallery.
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

    public function run(): void
    {
        $schema = TemplateFactory::exampleConfigSchema();
        $categories = TemplateCategory::query()->pluck('id', 'slug');
        $eventTypes = EventType::query()->pluck('id', 'slug');

        foreach (self::TEMPLATES as $index => $definition) {
            $categoryId = $categories[$definition['category']] ?? null;

            if ($categoryId === null) {
                continue;
            }

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
                    'status' => TemplateStatus::Draft,
                    'sort_order' => $index,
                ],
            );

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
