<?php

declare(strict_types=1);

namespace Tests\Feature\Layout;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_the_admin_dashboard_renders_the_adminlte_shell(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('app-wrapper', false)
            ->assertSee('app-sidebar', false)
            ->assertSee('data-lte-toggle="sidebar"', false);
    }

    public function test_the_client_dashboard_ships_the_sidebar_collapsed(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->withoutVite()
            ->actingAs($client)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('sidebar-collapse', false)
            ->assertSee('app-sidebar', false);
    }

    public function test_the_profile_page_renders_inside_the_shell_of_the_users_own_surface(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/profile')
            ->assertOk()
            ->assertSee('app-sidebar', false)
            ->assertSee(route('admin.dashboard'), false);

        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get('/profile')
            ->assertOk()
            ->assertSee('app-sidebar', false)
            ->assertSee(route('dashboard'), false);
    }

    public function test_the_login_page_uses_the_adminlte_look(): void
    {
        $this->withoutVite()
            ->get('/login')
            ->assertOk()
            ->assertSee('login-box', false)
            ->assertDontSee('login-page-tailwind', false);
    }

    /**
     * The two bundles must never meet on one page (docs/05 § 2). Asserting that
     * reads the built manifest, so it needs `npm run build` — the rest of the
     * suite runs without it.
     */
    public function test_a_dashboard_page_loads_the_bootstrap_bundle_and_not_tailwind(): void
    {
        $this->requireBuiltAssets();

        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get('/dashboard')
            ->assertOk()
            ->assertSee('/build/assets/dashboard-', false)
            ->assertDontSee('/build/assets/public-', false);
    }

    public function test_the_auth_pages_load_the_same_bundle_as_the_dashboard(): void
    {
        $this->requireBuiltAssets();

        $this->get('/login')
            ->assertOk()
            ->assertSee('/build/assets/dashboard-', false)
            ->assertDontSee('/build/assets/public-', false);
    }

    private function requireBuiltAssets(): void
    {
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Needs `npm run build`: this asserts which Vite bundle a page loads.');
        }
    }
}
