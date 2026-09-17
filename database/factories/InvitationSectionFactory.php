<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SectionKey;
use App\Models\Invitation;
use App\Models\InvitationSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationSection>
 */
class InvitationSectionFactory extends Factory
{
    protected $model = InvitationSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->randomElement(SectionKey::cases());

        return [
            'invitation_id' => Invitation::factory(),
            'section_key' => $key->value,
            'title' => null,
            'content' => null,
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 9),
        ];
    }

    public function hidden(): self
    {
        return $this->state(fn (): array => ['is_visible' => false]);
    }

    public function key(SectionKey $key, int $sortOrder = 0): self
    {
        return $this->state(fn (): array => [
            'section_key' => $key->value,
            'sort_order' => $sortOrder,
        ]);
    }
}
