<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\RoleHome;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Putri Ayu Lestari',
            'email' => 'putri@example.com',
            'phone' => '081234567890',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            ...$overrides,
        ];
    }

    public function test_registration_assigns_the_client_role(): void
    {
        $this->post('/register', $this->validPayload());

        $user = User::firstWhere('email', 'putri@example.com');

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('client'));
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_registration_never_hands_out_an_elevated_role(): void
    {
        $this->post('/register', $this->validPayload(['role' => 'super-admin']));

        $user = User::firstWhere('email', 'putri@example.com');

        $this->assertSame(['client'], $user->getRoleNames()->all());
    }

    public function test_registration_lands_the_new_client_on_the_dashboard(): void
    {
        $this->post('/register', $this->validPayload())
            ->assertRedirect(RoleHome::CLIENT);

        $this->assertAuthenticated();
    }

    public function test_phone_is_normalised_to_e164(): void
    {
        $this->post('/register', $this->validPayload());

        $this->assertSame('+6281234567890', User::firstWhere('email', 'putri@example.com')->phone);
    }

    public function test_phone_is_required(): void
    {
        $this->post('/register', $this->validPayload(['phone' => '']))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_an_invalid_phone_is_rejected(): void
    {
        $this->post('/register', $this->validPayload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_a_landline_is_rejected_because_the_channel_is_whatsapp(): void
    {
        // A valid Jakarta fixed line, but no use for a WhatsApp blast.
        $this->post('/register', $this->validPayload(['phone' => '0215678901']))
            ->assertSessionHasErrors('phone');
    }

    public function test_the_same_number_cannot_register_twice_in_a_different_shape(): void
    {
        User::factory()->create(['phone' => '+6281234567890']);

        // Local shape, same number.
        $this->post('/register', $this->validPayload(['phone' => '0812-3456-7890']))
            ->assertSessionHasErrors('phone');
    }
}
