<?php

declare(strict_types=1);

namespace Tests\Feature\Layout;

use App\Models\User;
use App\Support\Menu;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MenuPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_an_item_whose_route_does_not_exist_yet_is_skipped(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Later sessions register these; until then the sidebar must not link to
        // them. Session 5 registered the catalog taxonomy, so "Katalog" is here
        // now — "Pengguna" and "Pesanan" still are not.
        $this->assertFalse(Route::has('admin.users.index'));
        $this->assertFalse(Route::has('admin.orders.index'));

        $this->assertSame(['Dashboard', 'Katalog'], $this->labels(Menu::for('admin', $admin)));
    }

    public function test_a_parent_disappears_once_every_child_is_filtered_out(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->registerRoute('admin.templates.index', '/admin/templates');

        $this->assertSame(['Dashboard', 'Katalog'], $this->labels(Menu::for('admin', $admin)));

        // support holds neither templates.manage nor packages.manage, so the
        // whole "Katalog" branch goes with them.
        $support = User::factory()->create();
        $support->assignRole('support');

        $this->assertSame(['Dashboard'], $this->labels(Menu::for('admin', $support)));
    }

    public function test_a_client_only_sees_what_their_permissions_allow(): void
    {
        $this->registerRoute('client.affiliate.index', '/dashboard/affiliate');

        $client = User::factory()->create();
        $client->assignRole('client');

        // affiliates.view belongs to reseller, not client.
        $this->assertSame(['Dashboard'], $this->labels(Menu::for('client', $client)));

        $reseller = User::factory()->create();
        $reseller->assignRole('reseller');

        $this->assertSame(['Dashboard', 'Affiliate'], $this->labels(Menu::for('client', $reseller)));
    }

    public function test_a_guest_gets_no_menu_at_all(): void
    {
        $this->assertSame([], Menu::for('client', null));
    }

    /**
     * Stand in for a route a later session registers, so the menu item it
     * belongs to becomes visible.
     */
    private function registerRoute(string $name, string $uri): void
    {
        Route::middleware(['web', 'auth'])->get($uri, fn () => '')->name($name);
        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<string>
     */
    private function labels(array $items): array
    {
        return array_map(static fn (array $item): string => $item['label'], $items);
    }
}
