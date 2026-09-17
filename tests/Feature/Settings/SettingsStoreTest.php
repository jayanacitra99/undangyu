<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\SettingType;
use App\Facades\Setting;
use App\Models\Setting as SettingModel;
use App\Support\Settings;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_setting_round_trips_through_the_store(): void
    {
        Setting::set('site.name', 'Undangyu');

        $this->assertSame('Undangyu', Setting::get('site.name'));
        $this->assertDatabaseHas('settings', ['group' => 'site', 'key' => 'name', 'value' => 'Undangyu']);
    }

    public function test_each_type_comes_back_as_the_php_type_it_went_in_as(): void
    {
        Setting::set('invitation.default_active_days', 90);
        Setting::set('payment.manual_transfer_enabled', true);
        Setting::set('invitation.slug_blocklist', ['admin', 'login']);

        $this->assertSame(90, Setting::get('invitation.default_active_days'));
        $this->assertTrue(Setting::get('payment.manual_transfer_enabled'));
        $this->assertSame(['admin', 'login'], Setting::get('invitation.slug_blocklist'));
    }

    public function test_a_missing_key_falls_back_to_the_default(): void
    {
        $this->assertSame('fallback', Setting::get('site.nothing_here', 'fallback'));
        $this->assertNull(Setting::get('site.nothing_here'));
    }

    public function test_a_key_without_a_group_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Setting::set('name', 'Undangyu');
    }

    public function test_group_returns_only_that_group_keyed_short(): void
    {
        Setting::set('site.name', 'Undangyu');
        Setting::set('site.tagline', 'Undangan digital');
        Setting::set('seo.meta_title', 'Undangyu');

        $this->assertSame(
            ['name' => 'Undangyu', 'tagline' => 'Undangan digital'],
            Setting::group('site'),
        );
    }

    public function test_reads_are_cached_forever_and_the_write_flushes_them(): void
    {
        Setting::set('site.name', 'Undangyu');

        // The cache entry is written by the first read, not by the write.
        $this->assertNull(Cache::get(Settings::CACHE_KEY));
        $this->assertSame('Undangyu', Setting::get('site.name'));
        $this->assertSame('Undangyu', Cache::get(Settings::CACHE_KEY)['site.name']);

        Setting::set('site.name', 'Undangyu v2');

        $this->assertNull(Cache::get(Settings::CACHE_KEY));

        // The write dropped the entry; the next read repopulates it with the
        // new value rather than serving the old one.
        $this->assertSame('Undangyu v2', Setting::get('site.name'));
        $this->assertSame('Undangyu v2', Cache::get(Settings::CACHE_KEY)['site.name']);
    }

    public function test_repeated_reads_hit_the_database_once(): void
    {
        Setting::set('site.name', 'Undangyu');
        app(Settings::class)->flush();
        Cache::flush();

        DB::enableQueryLog();

        Setting::get('site.name');
        Setting::get('site.name');
        Setting::get('site.name');

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function test_an_existing_rows_type_wins_over_the_value_passed_in(): void
    {
        SettingModel::factory()->int(30)->create(['group' => 'invitation', 'key' => 'default_active_days']);

        Setting::set('invitation.default_active_days', '45');

        $this->assertSame(45, Setting::get('invitation.default_active_days'));
    }

    public function test_only_public_settings_are_exposed_to_the_invitation_page(): void
    {
        SettingModel::factory()->public()->create(['group' => 'site', 'key' => 'name', 'value' => 'Undangyu']);
        SettingModel::factory()->create(['group' => 'payment', 'key' => 'driver', 'value' => 'manual']);

        $this->assertSame(['site.name' => 'Undangyu'], Setting::public());
    }

    public function test_forget_removes_the_row_and_the_cached_value(): void
    {
        Setting::set('site.name', 'Undangyu');

        Setting::forget('site.name');

        $this->assertDatabaseMissing('settings', ['group' => 'site', 'key' => 'name']);
        $this->assertNull(Setting::get('site.name'));
    }

    public function test_the_seeder_is_idempotent_and_never_overwrites_an_edited_value(): void
    {
        $this->seed(SettingSeeder::class);

        Setting::set('site.name', 'Nama Baru');

        $this->seed(SettingSeeder::class);

        $this->assertSame('Nama Baru', Setting::get('site.name'));
        $this->assertSame(1, SettingModel::query()->where('group', 'site')->where('key', 'name')->count());
    }

    public function test_the_seeded_defaults_carry_the_types_from_the_spec(): void
    {
        $this->seed(SettingSeeder::class);

        $this->assertSame(SettingType::Int, SettingModel::query()->where('key', 'default_active_days')->sole()->type);
        $this->assertSame(SettingType::Json, SettingModel::query()->where('key', 'allowed_mimes')->sole()->type);
        $this->assertSame(SettingType::Bool, SettingModel::query()->where('key', 'manual_transfer_enabled')->sole()->type);
        $this->assertIsArray(Setting::get('media.allowed_mimes'));
    }

    public function test_payment_credentials_are_not_stored_as_settings(): void
    {
        $this->seed(SettingSeeder::class);

        // The driver name and the manual-transfer destination belong here —
        // finance edits the bank account without a deploy. Gateway keys and
        // secrets stay in env, and none of them may appear in this group.
        $this->assertSame([
            'driver',
            'manual_transfer_enabled',
            'manual_bank_name',
            'manual_account_number',
            'manual_account_name',
            'manual_instructions',
        ], array_keys(Setting::group('payment')));

        foreach (array_keys(Setting::group('payment')) as $key) {
            $this->assertStringNotContainsString('key', $key);
            $this->assertStringNotContainsString('secret', $key);
        }
    }
}
