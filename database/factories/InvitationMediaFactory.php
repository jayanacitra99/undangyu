<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationMedia>
 */
class InvitationMediaFactory extends Factory
{
    protected $model = InvitationMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::random(12);

        return [
            'invitation_id' => Invitation::factory(),
            'type' => MediaType::Image->value,
            'source' => MediaSource::Upload->value,
            'disk' => 'public',
            'path' => "invitations/gallery/{$name}.webp",
            'embed_url' => null,
            'thumbnail' => "invitations/gallery/{$name}-thumb.webp",
            'conversions' => [
                'thumb' => "invitations/gallery/{$name}-thumb.webp",
                'medium' => "invitations/gallery/{$name}-medium.webp",
            ],
            'caption' => fake()->randomElement(['Prewedding di Bromo', 'Sesi foto studio', 'Senja di Kuta', null]),
            'file_size' => fake()->numberBetween(180_000, 2_400_000),
            'is_cover' => false,
            'sort_order' => fake()->numberBetween(0, 12),
        ];
    }

    public function cover(): self
    {
        return $this->state(fn (): array => ['is_cover' => true, 'sort_order' => 0]);
    }

    public function music(): self
    {
        return $this->state(fn (): array => [
            'type' => MediaType::Audio->value,
            'source' => MediaSource::Library->value,
            'path' => 'library/audio/akad-instrumental.mp3',
            'thumbnail' => null,
            'conversions' => null,
            'caption' => 'Instrumental akustik',
            'file_size' => 3_800_000,
        ]);
    }

    public function embeddedVideo(): self
    {
        return $this->state(fn (): array => [
            'type' => MediaType::Video->value,
            'source' => MediaSource::Embed->value,
            'disk' => null,
            'path' => null,
            'embed_url' => 'https://www.youtube.com/watch?v='.fake()->lexify('???????????'),
            'conversions' => null,
            'file_size' => null,
        ]);
    }
}
