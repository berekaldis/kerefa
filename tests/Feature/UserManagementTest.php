<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_super_admin_can_view_users_list(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('admin@kerefa.com');
        $response->assertSee('User & Access Management', false);
    }

    public function test_super_admin_can_create_new_user(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();
        $branch = Branch::first();
        $dept = Department::first();
        $waiterRole = Role::where('slug', 'waiter')->first();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Abebe Bikila',
            'username' => 'abebe',
            'email' => 'abebe@kerefa.com',
            'phone' => '+251911223344',
            'password' => 'Password123!',
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'is_active' => '1',
            'roles' => [$waiterRole->id],
            'accessible_branches' => [$branch->id],
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'abebe@kerefa.com',
            'username' => 'abebe',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'module' => 'users',
        ]);
    }

    public function test_user_without_permission_cannot_access_user_management(): void
    {
        $waiter = User::where('username', 'waiter')->first();

        $response = $this->actingAs($waiter)->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();
        $staff = User::where('username', 'waiter')->first();

        $this->assertTrue($staff->is_active);

        $response = $this->actingAs($admin)->post(route('users.toggle-status', $staff));

        $response->assertRedirect();
        $staff->refresh();
        $this->assertFalse($staff->is_active);
    }
}
