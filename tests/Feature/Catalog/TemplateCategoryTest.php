<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\TemplateCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TemplateCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_the_seeder_ships_eight_categories_in_order(): void
    {
        $this->seed(TemplateCategorySeeder::class);

        $this->assertSame([
            'floral',
            'minimalis',
            'luxury',
            'islami',
            'rustic',
            'jawa',
            'modern',
            'dark',
        ], TemplateCategory::query()->ordered()->pluck('slug')->all());
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(TemplateCategorySeeder::class);

        TemplateCategory::query()->where('slug', 'dark')->update(['name' => 'Gelap']);

        $this->seed(TemplateCategorySeeder::class);

        $this->assertSame(8, TemplateCategory::query()->count());
        $this->assertSame('Gelap', TemplateCategory::query()->where('slug', 'dark')->value('name'));
    }

    public function test_a_deactivated_category_disappears_from_the_active_scope(): void
    {
        $this->seed(TemplateCategorySeeder::class);

        TemplateCategory::query()->where('slug', 'rustic')->update(['is_active' => false]);

        $active = TemplateCategory::query()->active()->pluck('slug')->all();

        $this->assertNotContains('rustic', $active);
        $this->assertCount(7, $active);

        $this->actingAs($this->catalogAdmin())
            ->get(route('admin.template-categories.index'))
            ->assertOk()
            ->assertSee('rustic');
    }

    public function test_an_admin_can_create_edit_and_delete_a_category(): void
    {
        $admin = $this->catalogAdmin();

        $this->actingAs($admin)
            ->post(route('admin.template-categories.store'), [
                'name' => 'Pastel',
                'slug' => '',
                'description' => 'Warna lembut.',
                'is_active' => '1',
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.template-categories.index'))
            ->assertSessionHasNoErrors();

        $category = TemplateCategory::query()->sole();
        $this->assertSame('pastel', $category->slug);

        $this->actingAs($admin)
            ->put(route('admin.template-categories.update', $category), [
                'name' => 'Pastel Lembut',
                'slug' => 'pastel',
                'description' => null,
                'sort_order' => 2,
                // is_active omitted — switched off.
            ])
            ->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('Pastel Lembut', $category->name);
        $this->assertFalse($category->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.template-categories.destroy', $category))
            ->assertRedirect(route('admin.template-categories.index'));

        $this->assertDatabaseMissing('template_categories', ['id' => $category->id]);
    }

    public function test_a_duplicate_slug_is_rejected(): void
    {
        TemplateCategory::factory()->create(['slug' => 'floral']);

        $this->actingAs($this->catalogAdmin())
            ->post(route('admin.template-categories.store'), [
                'name' => 'Floral',
                'slug' => 'floral',
                'description' => null,
                'is_active' => '1',
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, TemplateCategory::query()->count());
    }

    public function test_dragging_rows_writes_the_new_sort_order(): void
    {
        $this->seed(TemplateCategorySeeder::class);

        $reversed = array_reverse(TemplateCategory::query()->ordered()->pluck('id')->all());

        $this->actingAs($this->catalogAdmin())
            ->postJson(route('admin.template-categories.reorder'), ['ids' => $reversed])
            ->assertOk();

        $this->assertSame($reversed, TemplateCategory::query()->ordered()->pluck('id')->all());
    }

    public function test_a_user_without_the_permission_cannot_read_or_write(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support');

        $category = TemplateCategory::factory()->create();

        $this->actingAs($support)->get(route('admin.template-categories.index'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.template-categories.create'))->assertForbidden();
        $this->actingAs($support)
            ->post(route('admin.template-categories.store'), [
                'name' => 'Diretas',
                'slug' => 'diretas',
                'is_active' => '1',
                'sort_order' => 0,
            ])
            ->assertForbidden();
        $this->actingAs($support)->delete(route('admin.template-categories.destroy', $category))->assertForbidden();

        $this->assertSame(1, TemplateCategory::query()->count());
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('admin.template-categories.index'))->assertRedirect('/login');
    }

    private function catalogAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
