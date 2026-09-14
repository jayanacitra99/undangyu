<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

/**
 * The eight template categories the catalog ships with (playbook 5.2).
 *
 * Idempotent for the same reason as the event types: an admin's renamed category
 * survives a re-seed.
 */
class TemplateCategorySeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, description: string}>
     */
    public const CATEGORIES = [
        ['name' => 'Floral', 'slug' => 'floral', 'description' => 'Bunga dan dedaunan, lembut dan romantis.'],
        ['name' => 'Minimalis', 'slug' => 'minimalis', 'description' => 'Bersih, banyak ruang kosong, tipografi sederhana.'],
        ['name' => 'Luxury', 'slug' => 'luxury', 'description' => 'Emas, marmer, dan detail mewah.'],
        ['name' => 'Islami', 'slug' => 'islami', 'description' => 'Ornamen geometris dan kaligrafi.'],
        ['name' => 'Rustic', 'slug' => 'rustic', 'description' => 'Kayu, kertas kraft, dan nuansa pedesaan.'],
        ['name' => 'Jawa', 'slug' => 'jawa', 'description' => 'Batik, wayang, dan motif tradisional Jawa.'],
        ['name' => 'Modern', 'slug' => 'modern', 'description' => 'Warna berani dan tata letak kontemporer.'],
        ['name' => 'Dark', 'slug' => 'dark', 'description' => 'Latar gelap dengan aksen kontras.'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            TemplateCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
