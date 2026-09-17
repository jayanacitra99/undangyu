<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /**
     * The schema every seeded template ships with — the example in
     * docs/05-technical-architecture.md § 5, verbatim.
     *
     * @return array<string, mixed>
     */
    public static function exampleConfigSchema(): array
    {
        return [
            'colors' => [
                'primary' => ['type' => 'color', 'default' => '#8B7355', 'label' => 'Warna utama'],
                'secondary' => ['type' => 'color', 'default' => '#D4C5B0', 'label' => 'Warna pendukung'],
                'text' => ['type' => 'color', 'default' => '#3A3A3A', 'label' => 'Warna teks'],
            ],
            'fonts' => [
                'heading' => [
                    'type' => 'select',
                    'options' => ['Playfair Display', 'Cormorant', 'Marcellus'],
                    'default' => 'Playfair Display',
                ],
                'body' => [
                    'type' => 'select',
                    'options' => ['Lato', 'Montserrat', 'Inter'],
                    'default' => 'Lato',
                ],
            ],
            'options' => [
                'cover_style' => ['type' => 'select', 'options' => ['full', 'framed', 'split'], 'default' => 'full'],
                'animation' => ['type' => 'select', 'options' => ['fade', 'slide', 'none'], 'default' => 'fade'],
                'show_countdown' => ['type' => 'boolean', 'default' => true],
                'show_music_button' => ['type' => 'boolean', 'default' => true],
            ],
        ];
    }

    /**
     * The defaults a client starts from: every schema key at its declared default.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public static function defaultsFor(array $schema): array
    {
        $defaults = [];

        foreach ($schema as $group => $fields) {
            foreach ($fields as $key => $field) {
                $defaults[$group][$key] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // The suffix is what carries uniqueness — the name pool is small enough
        // that a unique() on it alone runs out after ten templates.
        $name = fake()->randomElement([
            'Sekar Ayu', 'Melati Putih', 'Senja Emas', 'Kirana', 'Anggrek Bulan',
            'Bumi Langit', 'Puspa Rinjani', 'Cendana', 'Larasati', 'Nirwana',
        ]).' '.fake()->unique()->numberBetween(1, 99999);

        $schema = self::exampleConfigSchema();
        $viewKey = Str::slug($name);

        return [
            'template_category_id' => TemplateCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Desain '.mb_strtolower($name).' dengan palet hangat dan tipografi klasik.',
            'thumbnail' => 'templates/thumbnails/'.$viewKey.'.webp',
            'view_key' => $viewKey,
            'version' => '1.0.0',
            'min_package_id' => null,
            'config_schema' => $schema,
            'default_config' => self::defaultsFor($schema),
            'demo_data' => [
                'couple' => ['bride' => 'Ayu Lestari', 'groom' => 'Bagus Prakoso'],
                'venue' => 'Gedung Serbaguna Puri Agung, Denpasar',
            ],
            'is_premium' => false,
            'extra_price' => 0,
            'status' => TemplateStatus::Draft,
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }

    public function published(): self
    {
        return $this->state(fn (): array => ['status' => TemplateStatus::Published]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => ['status' => TemplateStatus::Archived]);
    }

    public function premium(int|string $extraPrice = 150000): self
    {
        return $this->state(fn (): array => [
            'is_premium' => true,
            'extra_price' => $extraPrice,
        ]);
    }
}
