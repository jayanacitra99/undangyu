<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PersonRole;
use App\Enums\SectionKey;
use App\Models\EventType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventType>
 */
class EventTypeFactory extends Factory
{
    protected $model = EventType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Pernikahan', 'Aqiqah', 'Khitanan', 'Wisuda', 'Syukuran']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'icon' => 'bi-calendar-event',
            'person_roles' => [PersonRole::Host->value],
            'default_sections' => [
                SectionKey::Cover->value,
                SectionKey::Persons->value,
                SectionKey::Events->value,
                SectionKey::Rsvp->value,
            ],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
