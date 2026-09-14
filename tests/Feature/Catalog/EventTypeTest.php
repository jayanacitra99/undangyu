<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Enums\PersonRole;
use App\Enums\SectionKey;
use App\Models\EventType;
use App\Models\User;
use Database\Seeders\EventTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_the_seeder_ships_eight_types_in_order(): void
    {
        $this->seed(EventTypeSeeder::class);

        $slugs = EventType::query()->ordered()->pluck('slug')->all();

        $this->assertSame([
            'pernikahan',
            'tunangan',
            'ulang-tahun',
            'aqiqah',
            'khitanan',
            'wisuda',
            'corporate',
            'umum',
        ], $slugs);
    }

    public function test_person_roles_and_default_sections_come_back_as_arrays(): void
    {
        $this->seed(EventTypeSeeder::class);

        $wedding = EventType::query()->where('slug', 'pernikahan')->sole();

        $this->assertSame([PersonRole::Bride->value, PersonRole::Groom->value], $wedding->person_roles);
        $this->assertContains(SectionKey::Story->value, $wedding->default_sections);
        $this->assertSame(SectionKey::Cover->value, $wedding->default_sections[0]);

        // A corporate invite has no story and no gift section.
        $corporate = EventType::query()->where('slug', 'corporate')->sole();

        $this->assertSame([PersonRole::Host->value], $corporate->person_roles);
        $this->assertNotContains(SectionKey::Story->value, $corporate->default_sections);
        $this->assertNotContains(SectionKey::Gifts->value, $corporate->default_sections);
    }

    public function test_every_seeded_role_and_section_is_a_known_enum_case(): void
    {
        $this->seed(EventTypeSeeder::class);

        foreach (EventType::all() as $type) {
            foreach ($type->person_roles as $role) {
                $this->assertNotNull(PersonRole::tryFrom($role), "Unknown person role [{$role}].");
            }

            foreach ($type->default_sections as $section) {
                $this->assertNotNull(SectionKey::tryFrom($section), "Unknown section key [{$section}].");
            }
        }
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(EventTypeSeeder::class);

        EventType::query()->where('slug', 'umum')->update(['name' => 'Acara Umum']);

        $this->seed(EventTypeSeeder::class);

        $this->assertSame(8, EventType::query()->count());
        $this->assertSame('Acara Umum', EventType::query()->where('slug', 'umum')->value('name'));
    }

    public function test_a_deactivated_type_disappears_from_the_active_scope(): void
    {
        $this->seed(EventTypeSeeder::class);

        EventType::query()->where('slug', 'khitanan')->update(['is_active' => false]);

        $active = EventType::query()->active()->pluck('slug')->all();

        $this->assertNotContains('khitanan', $active);
        $this->assertContains('pernikahan', $active);
        $this->assertCount(7, $active);

        // The admin list still shows it — deactivating is not deleting.
        $this->actingAs($this->catalogAdmin())
            ->get(route('admin.event-types.index'))
            ->assertOk()
            ->assertSee('khitanan');
    }

    public function test_an_admin_can_create_a_type(): void
    {
        $this->actingAs($this->catalogAdmin())
            ->post(route('admin.event-types.store'), [
                'name' => 'Syukuran',
                'slug' => '',
                'icon' => 'bi-cake',
                'person_roles' => [PersonRole::Host->value],
                'default_sections' => [SectionKey::Cover->value, SectionKey::Events->value],
                'is_active' => '1',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('admin.event-types.index'))
            ->assertSessionHasNoErrors();

        $type = EventType::query()->sole();

        // A blank slug follows the name.
        $this->assertSame('syukuran', $type->slug);
        $this->assertSame([SectionKey::Cover->value, SectionKey::Events->value], $type->default_sections);
        $this->assertTrue($type->is_active);
    }

    public function test_an_admin_can_edit_and_deactivate_a_type(): void
    {
        $type = EventType::factory()->create(['slug' => 'wisuda', 'name' => 'Wisuda']);

        $this->actingAs($this->catalogAdmin())
            ->put(route('admin.event-types.update', $type), [
                'name' => 'Wisuda Sarjana',
                'slug' => 'wisuda',
                'icon' => 'bi-mortarboard',
                'person_roles' => [PersonRole::Graduate->value],
                'default_sections' => [SectionKey::Cover->value],
                'sort_order' => 0,
                // is_active omitted — the switch was turned off.
            ])
            ->assertRedirect(route('admin.event-types.index'))
            ->assertSessionHasNoErrors();

        $type->refresh();

        $this->assertSame('Wisuda Sarjana', $type->name);
        $this->assertFalse($type->is_active);
    }

    public function test_an_admin_can_delete_a_type(): void
    {
        $type = EventType::factory()->create();

        $this->actingAs($this->catalogAdmin())
            ->delete(route('admin.event-types.destroy', $type))
            ->assertRedirect(route('admin.event-types.index'));

        $this->assertDatabaseMissing('event_types', ['id' => $type->id]);
    }

    public function test_a_duplicate_slug_is_rejected_but_the_row_keeps_its_own(): void
    {
        $first = EventType::factory()->create(['slug' => 'pernikahan']);
        $second = EventType::factory()->create(['slug' => 'tunangan']);

        $this->actingAs($this->catalogAdmin())
            ->post(route('admin.event-types.store'), $this->validPayload(['slug' => 'pernikahan']))
            ->assertSessionHasErrors('slug');

        // Editing a row without changing its slug is not a duplicate.
        $this->actingAs($this->catalogAdmin())
            ->put(route('admin.event-types.update', $second), $this->validPayload([
                'slug' => 'tunangan',
                'name' => 'Tunangan',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, EventType::query()->count());
        $this->assertSame('pernikahan', $first->refresh()->slug);
    }

    public function test_an_unknown_role_or_section_is_rejected(): void
    {
        $admin = $this->catalogAdmin();

        $this->actingAs($admin)
            ->post(route('admin.event-types.store'), $this->validPayload(['person_roles' => ['dragon']]))
            ->assertSessionHasErrors('person_roles.0');

        $this->actingAs($admin)
            ->post(route('admin.event-types.store'), $this->validPayload(['default_sections' => ['fireworks']]))
            ->assertSessionHasErrors('default_sections.0');

        $this->assertSame(0, EventType::query()->count());
    }

    public function test_dragging_rows_writes_the_new_sort_order(): void
    {
        $this->seed(EventTypeSeeder::class);

        $ids = EventType::query()->ordered()->pluck('id')->all();
        $reversed = array_reverse($ids);

        $this->actingAs($this->catalogAdmin())
            ->postJson(route('admin.event-types.reorder'), ['ids' => $reversed])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame($reversed, EventType::query()->ordered()->pluck('id')->all());
    }

    public function test_a_reorder_payload_of_foreign_ids_changes_nothing(): void
    {
        $type = EventType::factory()->create(['sort_order' => 5]);

        $this->actingAs($this->catalogAdmin())
            ->postJson(route('admin.event-types.reorder'), ['ids' => [$type->id + 999]])
            ->assertOk();

        $this->assertSame(5, $type->refresh()->sort_order);
    }

    public function test_a_user_without_the_permission_cannot_read_or_write(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support');

        $type = EventType::factory()->create();

        $this->actingAs($support)->get(route('admin.event-types.index'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.event-types.create'))->assertForbidden();
        $this->actingAs($support)->post(route('admin.event-types.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($support)->put(route('admin.event-types.update', $type), $this->validPayload())->assertForbidden();
        $this->actingAs($support)->delete(route('admin.event-types.destroy', $type))->assertForbidden();
        $this->actingAs($support)->postJson(route('admin.event-types.reorder'), ['ids' => [$type->id]])->assertForbidden();

        $this->assertDatabaseHas('event_types', ['id' => $type->id]);
        $this->assertSame(1, EventType::query()->count());
    }

    public function test_a_client_cannot_reach_the_admin_surface_at_all(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get(route('admin.event-types.index'))->assertForbidden();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('admin.event-types.index'))->assertRedirect('/login');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Acara Baru',
            'slug' => 'acara-baru',
            'icon' => 'bi-calendar-event',
            'person_roles' => [PersonRole::Host->value],
            'default_sections' => [SectionKey::Cover->value],
            'is_active' => '1',
            'sort_order' => 0,
        ], $overrides);
    }

    private function catalogAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
