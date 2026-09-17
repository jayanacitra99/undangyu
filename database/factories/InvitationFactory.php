<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use App\Models\EventType;
use App\Models\Invitation;
use App\Models\Package;
use App\Models\Template;
use App\Models\User;
use App\Services\Entitlements\EntitlementResolver;
use App\Support\InvitationSettings;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * The feature toggles a new invitation starts with — from the same
     * definition the builder and the FormRequest use, so a new setting cannot
     * exist in one place and not the other.
     *
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return InvitationSettings::defaults();
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bride = fake()->randomElement(['Ayu Lestari', 'Sekar Wulandari', 'Dinda Pratiwi', 'Ratna Kusuma']);
        $groom = fake()->randomElement(['Bagus Prakoso', 'Raka Adhitama', 'Yoga Mahendra', 'Dimas Saputra']);
        $title = "{$bride} & {$groom}";

        return [
            'user_id' => User::factory(),
            'created_by' => null,
            'order_id' => null,
            'event_type_id' => EventType::factory(),
            'template_id' => Template::factory(),
            'template_version' => '1.0.0',
            'package_id' => Package::factory(),
            'slug' => Str::slug($bride.'-'.$groom).'-'.fake()->unique()->numberBetween(100, 99999),
            'custom_domain' => null,
            'title' => $title,
            'status' => InvitationStatus::Draft,
            'visibility' => InvitationVisibility::Public,
            'password' => null,
            'language' => 'id',
            'timezone' => 'Asia/Jakarta',
            'theme_config' => null,
            'settings' => self::defaultSettings(),
            // Overwritten by the `forPackage` state where a real package matters.
            'entitlements' => [
                'max_guests' => 500,
                'max_photos' => 30,
                'max_video_mb' => 100,
                'whatsapp_quota' => 500,
                'custom_domain' => false,
                'remove_watermark' => true,
                'rsvp' => true,
                'guestbook' => true,
                'checkin' => false,
                'music' => true,
                'live_stream' => false,
                'export' => false,
                'analytics_advanced' => false,
            ],
            'meta_title' => $title,
            'meta_description' => "Undangan pernikahan {$bride} dan {$groom}.",
            'og_image_path' => null,
            'published_at' => null,
            'expires_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (): array => [
            'status' => InvitationStatus::Published,
            'published_at' => now()->subDays(3),
            'expires_at' => now()->addMonths(6),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'status' => InvitationStatus::Expired,
            'published_at' => now()->subMonths(8),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function passwordProtected(string $password = 'rahasia'): self
    {
        return $this->state(fn (): array => [
            'visibility' => InvitationVisibility::Password,
            'password' => $password,
        ]);
    }

    /**
     * Snapshot a real package's entitlements, the way provisioning does.
     */
    public function forPackage(Package $package): self
    {
        return $this->state(fn (): array => [
            'package_id' => $package->getKey(),
            'entitlements' => app(EntitlementResolver::class)->resolve($package),
        ]);
    }
}
