<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FeatureKey;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * The four packages the product launches with (playbook 7.5).
 *
 * A quota value of `null` means unlimited. A switch key that is absent from a
 * package's list is off — the resolver fills it in, so the lists below only
 * name what a package actually grants.
 *
 * Idempotent on the slug, like the rest of the catalog seeders: an admin's
 * edited price survives a re-seed.
 */
class PackageSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, description: string, price: int, discount_price: ?int, active_days: int, is_featured: bool, features: array<string, int|bool|null>}>
     */
    public const PACKAGES = [
        [
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Coba dulu. Satu undangan dengan watermark Undangyu.',
            'price' => 0,
            'discount_price' => null,
            'active_days' => 30,
            'is_featured' => false,
            'features' => [
                'max_guests' => 30,
                'max_photos' => 5,
                'max_video_mb' => 0,
                'whatsapp_quota' => 0,
                'rsvp' => true,
            ],
        ],
        [
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Untuk acara kecil. Tanpa watermark, buku tamu aktif.',
            'price' => 99000,
            'discount_price' => 79000,
            'active_days' => 180,
            'is_featured' => false,
            'features' => [
                'max_guests' => 200,
                'max_photos' => 20,
                'max_video_mb' => 50,
                'whatsapp_quota' => 200,
                'remove_watermark' => true,
                'rsvp' => true,
                'guestbook' => true,
                'music' => true,
            ],
        ],
        [
            'name' => 'Premium',
            'slug' => 'premium',
            'description' => 'Pilihan terbanyak. Check-in tamu, ekspor data, kuota WhatsApp besar.',
            'price' => 249000,
            'discount_price' => 199000,
            'active_days' => 365,
            'is_featured' => true,
            'features' => [
                'max_guests' => 1000,
                'max_photos' => 60,
                'max_video_mb' => 200,
                'whatsapp_quota' => 1000,
                'remove_watermark' => true,
                'rsvp' => true,
                'guestbook' => true,
                'checkin' => true,
                'music' => true,
                'export' => true,
            ],
        ],
        [
            'name' => 'Exclusive',
            'slug' => 'exclusive',
            'description' => 'Tanpa batas tamu dan foto, domain sendiri, live streaming.',
            'price' => 599000,
            'discount_price' => null,
            'active_days' => 365,
            'is_featured' => false,
            'features' => [
                'max_guests' => null,
                'max_photos' => null,
                'max_video_mb' => 1000,
                'whatsapp_quota' => 5000,
                'custom_domain' => true,
                'remove_watermark' => true,
                'rsvp' => true,
                'guestbook' => true,
                'checkin' => true,
                'music' => true,
                'live_stream' => true,
                'export' => true,
                'analytics_advanced' => true,
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::PACKAGES as $index => $definition) {
            $package = Package::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'price' => $definition['price'],
                    'discount_price' => $definition['discount_price'],
                    'currency' => 'IDR',
                    'active_days' => $definition['active_days'],
                    'is_featured' => $definition['is_featured'],
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );

            foreach ($definition['features'] as $key => $value) {
                $featureKey = FeatureKey::from($key);

                $package->features()->updateOrCreate(
                    ['feature_key' => $featureKey->value],
                    [
                        'feature_value' => match (true) {
                            $value === null => null,
                            $featureKey->isQuota() => (string) (int) $value,
                            default => $value ? 'true' : 'false',
                        },
                        'is_unlimited' => $featureKey->isQuota() && $value === null,
                    ],
                );
            }
        }
    }
}
