<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * The defaults every environment starts with (M11.8 / playbook 4.3).
 *
 * Rows already present keep their value — re-running this seeder adds new keys
 * without resetting what an admin has changed.
 *
 * Payment and WhatsApp credentials are NOT here: this table is editable from the
 * admin panel, so secrets stay in the environment. Only the driver name lives in
 * a setting, so the gateway can be switched without a deploy.
 */
class SettingSeeder extends Seeder
{
    /**
     * group => key => [value, type, is_public]
     *
     * @var array<string, array<string, array{0: mixed, 1: SettingType, 2: bool}>>
     */
    public const DEFAULTS = [
        'site' => [
            'name' => ['Undangyu', SettingType::String, true],
            'tagline' => ['Undangan digital untuk hari istimewa Anda', SettingType::String, true],
            'logo' => [null, SettingType::String, true],
            'contact_email' => ['halo@undangyu.id', SettingType::String, true],
            'whatsapp_number' => ['+628123456789', SettingType::String, true],
        ],

        'payment' => [
            // Keys and secrets stay in env — this names the driver only.
            'driver' => ['manual', SettingType::String, false],
            'manual_transfer_enabled' => [true, SettingType::Bool, false],
        ],

        'invitation' => [
            'default_active_days' => [90, SettingType::Int, false],
            'slug_blocklist' => [
                ['admin', 'dashboard', 'login', 'register', 'scanner', 'webhooks', 'api', 'undangyu'],
                SettingType::Json,
                false,
            ],
        ],

        'media' => [
            'max_upload_mb' => [10, SettingType::Int, false],
            'allowed_mimes' => [
                ['image/jpeg', 'image/png', 'image/webp', 'audio/mpeg'],
                SettingType::Json,
                false,
            ],
        ],

        'seo' => [
            'meta_title' => ['Undangyu — Undangan Digital', SettingType::String, true],
            'meta_description' => [
                'Buat undangan digital pernikahan dan acara Anda, bagikan lewat WhatsApp, kelola tamu dan RSVP dalam satu tempat.',
                SettingType::String,
                true,
            ],
            'meta_image' => [null, SettingType::String, true],
        ],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $group => $settings) {
            foreach ($settings as $key => [$value, $type, $isPublic]) {
                Setting::query()->firstOrCreate(
                    ['group' => $group, 'key' => $key],
                    [
                        'value' => $type->serialize($value),
                        'type' => $type,
                        'is_public' => $isPublic,
                    ],
                );
            }
        }

        Cache::forget(Settings::CACHE_KEY);
    }
}
