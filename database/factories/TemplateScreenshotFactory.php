<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Template;
use App\Models\TemplateScreenshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateScreenshot>
 */
class TemplateScreenshotFactory extends Factory
{
    protected $model = TemplateScreenshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'path' => 'templates/screenshots/'.fake()->unique()->slug(2).'.webp',
            'caption' => fake()->randomElement(['Sampul', 'Mempelai', 'Galeri', 'Lokasi acara', 'Buku tamu']),
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }
}
