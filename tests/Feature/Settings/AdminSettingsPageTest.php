<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Facades\Setting;
use App\Models\Setting as SettingModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->withoutVite();
    }

    public function test_an_admin_with_the_permission_sees_every_group_as_a_tab(): void
    {
        $this->actingAs($this->adminWithSettingsPermission())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('#tab-site', false)
            ->assertSee('#tab-payment', false)
            ->assertSee('#tab-invitation', false)
            ->assertSee('#tab-media', false)
            ->assertSee('#tab-seo', false);
    }

    public function test_saving_persists_the_values_and_survives_a_reload(): void
    {
        $admin = $this->adminWithSettingsPermission();

        $this->actingAs($admin)
            ->put('/admin/settings', [
                'tab' => 'site',
                'settings' => [
                    'site' => [
                        'name' => 'Undangyu Baru',
                        'tagline' => 'Tagline baru',
                        'logo' => null,
                        'contact_email' => 'halo@undangyu.id',
                        'whatsapp_number' => '+628123456789',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'site']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Undangyu Baru', Setting::get('site.name'));

        $this->actingAs($admin)
            ->get('/admin/settings?tab=site')
            ->assertOk()
            ->assertSee('Undangyu Baru', false);
    }

    public function test_a_group_the_form_did_not_show_is_left_alone(): void
    {
        $this->actingAs($this->adminWithSettingsPermission())
            ->put('/admin/settings', [
                'tab' => 'site',
                'settings' => ['site' => ['name' => 'Undangyu Baru']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(90, Setting::get('invitation.default_active_days'));
        $this->assertSame('manual', Setting::get('payment.driver'));
    }

    public function test_an_unchecked_checkbox_saves_false_rather_than_keeping_the_old_value(): void
    {
        $this->assertTrue(Setting::get('payment.manual_transfer_enabled'));

        $this->actingAs($this->adminWithSettingsPermission())
            ->put('/admin/settings', [
                'tab' => 'payment',
                'settings' => ['payment' => ['driver' => 'manual']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse(Setting::get('payment.manual_transfer_enabled'));
    }

    public function test_each_type_is_validated_by_its_own_rule(): void
    {
        $admin = $this->adminWithSettingsPermission();

        $this->actingAs($admin)
            ->put('/admin/settings', [
                'tab' => 'invitation',
                'settings' => ['invitation' => ['default_active_days' => 'ninety']],
            ])
            ->assertSessionHasErrors('settings.invitation.default_active_days');

        $this->actingAs($admin)
            ->put('/admin/settings', [
                'tab' => 'media',
                'settings' => ['media' => ['max_upload_mb' => 10, 'allowed_mimes' => 'not json']],
            ])
            ->assertSessionHasErrors('settings.media.allowed_mimes');

        // The rejected values never landed.
        $this->assertSame(90, Setting::get('invitation.default_active_days'));
        $this->assertIsArray(Setting::get('media.allowed_mimes'));
    }

    public function test_a_rejected_save_reopens_the_tab_that_holds_the_error(): void
    {
        $this->actingAs($this->adminWithSettingsPermission())
            ->from('/admin/settings?tab=site')
            ->put('/admin/settings', [
                'tab' => 'media',
                'settings' => ['media' => ['max_upload_mb' => 10, 'allowed_mimes' => 'not json']],
            ])
            ->assertSessionHasErrors('settings.media.allowed_mimes');

        // Following the redirect back, the media pane is the open one.
        $this->actingAs($this->adminWithSettingsPermission())
            ->get('/admin/settings?tab=site')
            ->assertOk();

        $this->followingRedirects()
            ->actingAs($this->adminWithSettingsPermission())
            ->from('/admin/settings?tab=site')
            ->put('/admin/settings', [
                'tab' => 'media',
                'settings' => ['media' => ['max_upload_mb' => 10, 'allowed_mimes' => 'not json']],
            ])
            ->assertOk()
            ->assertSee('class="tab-pane fade show active" id="tab-media"', false)
            ->assertSee('Format JSON.', false);
    }

    public function test_valid_json_is_stored_as_an_array(): void
    {
        $this->actingAs($this->adminWithSettingsPermission())
            ->put('/admin/settings', [
                'tab' => 'media',
                'settings' => ['media' => [
                    'max_upload_mb' => 25,
                    'allowed_mimes' => '["image/jpeg","image/png"]',
                ]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['image/jpeg', 'image/png'], Setting::get('media.allowed_mimes'));
        $this->assertSame(25, Setting::get('media.max_upload_mb'));
    }

    public function test_a_key_with_no_row_is_ignored_rather_than_created(): void
    {
        $this->actingAs($this->adminWithSettingsPermission())
            ->put('/admin/settings', [
                'tab' => 'site',
                'settings' => ['site' => ['name' => 'Undangyu', 'smuggled' => 'nope']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('settings', ['key' => 'smuggled']);
    }

    public function test_an_admin_without_the_permission_is_forbidden(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support');

        $this->actingAs($support)->get('/admin/settings')->assertForbidden();

        $this->actingAs($support)
            ->put('/admin/settings', ['settings' => ['site' => ['name' => 'Diretas']]])
            ->assertForbidden();

        $this->assertSame('Undangyu', Setting::get('site.name'));
    }

    public function test_a_client_cannot_reach_the_settings_page_at_all(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get('/admin/settings')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
    }

    public function test_the_sidebar_links_to_settings_only_for_those_who_may_manage_them(): void
    {
        $this->actingAs($this->adminWithSettingsPermission())
            ->get('/admin')
            ->assertOk()
            ->assertSee(route('admin.settings.edit'), false);

        $support = User::factory()->create();
        $support->assignRole('support');

        $this->actingAs($support)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(route('admin.settings.edit'), false);
    }

    public function test_the_page_flags_which_settings_are_public(): void
    {
        $this->assertTrue(SettingModel::query()->where('group', 'site')->where('key', 'name')->sole()->is_public);

        $this->actingAs($this->adminWithSettingsPermission())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Tampil di halaman undangan publik.');
    }

    private function adminWithSettingsPermission(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }
}
