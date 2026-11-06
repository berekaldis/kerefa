<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('KEREFA RESTAURANT');
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@kerefa.com',
            'username' => 'staff1',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'staff@kerefa.com',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'auth',
        ]);
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'email' => 'chef@kerefa.com',
            'username' => 'headchef',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'headchef',
            'password' => 'Secret123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'waiter1@kerefa.com',
            'password' => Hash::make('CorrectPassword1!'),
        ]);

        $this->post('/login', [
            'login' => 'waiter1@kerefa.com',
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
    }

    public function test_deactivated_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@kerefa.com',
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'login' => 'disabled@kerefa.com',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('email', 'admin@kerefa.com')->first();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
            'module' => 'auth',
        ]);
    }
}
