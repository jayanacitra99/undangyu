<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\RoleHome;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleHomes(): array
    {
        return [
            'super-admin' => ['super-admin', RoleHome::ADMIN],
            'admin' => ['admin', RoleHome::ADMIN],
            'support' => ['support', RoleHome::ADMIN],
            'client' => ['client', RoleHome::CLIENT],
            'reseller' => ['reseller', RoleHome::CLIENT],
            'usher' => ['usher', RoleHome::USHER],
        ];
    }

    #[DataProvider('roleHomes')]
    public function test_login_redirects_by_role(string $role, string $expected): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($expected);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_records_the_last_login_time(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);
        $user->assignRole('client');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_user_with_no_role_is_not_sent_to_a_surface_they_cannot_enter(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/');
    }
}
