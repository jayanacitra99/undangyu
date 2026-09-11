<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_the_six_roles_from_m1_4_are_seeded(): void
    {
        $this->assertSame(
            ['admin', 'client', 'reseller', 'super-admin', 'support', 'usher'],
            Role::orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_a_client_cannot_reach_the_admin_surface(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get('/admin')->assertForbidden();
    }

    public function test_an_admin_can_reach_the_admin_surface(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_an_admin_cannot_reach_the_client_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
    }

    public function test_a_client_can_reach_the_client_dashboard(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get('/dashboard')->assertOk();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_super_admin_passes_every_gate_without_holding_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        // Nothing is assigned directly — Gate::before does the work.
        $this->assertEmpty($superAdmin->getAllPermissions());

        $this->assertTrue($superAdmin->can('settings.manage'));
        $this->assertTrue($superAdmin->can('withdrawals.approve'));
        $this->assertTrue($superAdmin->can('some.permission.that.does.not.exist.yet'));

        $this->actingAs($superAdmin)->get('/admin')->assertOk();
    }

    public function test_support_gets_read_only_orders_and_users(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support');

        $this->assertTrue($support->can('orders.viewAny'));
        $this->assertFalse($support->can('orders.manage'));

        $this->assertTrue($support->can('users.viewAny'));
        $this->assertFalse($support->can('users.manage'));

        $this->assertTrue($support->can('users.impersonate'));
        $this->assertFalse($support->can('payments.verify'));
    }

    public function test_an_usher_may_only_scan(): void
    {
        $usher = User::factory()->create();
        $usher->assignRole('usher');

        $this->assertTrue($usher->can('checkins.scan'));
        $this->assertFalse($usher->can('invitations.viewAny'));
        $this->assertFalse($usher->can('guests.viewAny'));

        $this->actingAs($usher)->get('/scanner')->assertOk();
        $this->actingAs($usher)->get('/admin')->assertForbidden();
        $this->actingAs($usher)->get('/dashboard')->assertForbidden();
    }

    public function test_a_reseller_gets_client_permissions_plus_the_affiliate_dashboard(): void
    {
        $reseller = User::factory()->create();
        $reseller->assignRole('reseller');

        $this->assertTrue($reseller->can('invitations.create'));
        $this->assertTrue($reseller->can('blasts.send'));
        $this->assertTrue($reseller->can('affiliates.view'));

        $this->assertFalse($reseller->can('withdrawals.approve'));
        $this->assertFalse($reseller->can('packages.manage'));
    }

    public function test_only_super_admin_manages_settings_and_roles(): void
    {
        foreach (['admin', 'support', 'client', 'reseller', 'usher'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->assertFalse(
                $user->can('settings.manage'),
                "The {$role} role must not manage settings.",
            );
            $this->assertFalse(
                $user->can('roles.manage'),
                "The {$role} role must not manage roles.",
            );
        }
    }
}
