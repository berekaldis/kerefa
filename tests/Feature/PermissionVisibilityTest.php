<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_staff_without_admin_permissions_does_not_see_restricted_buttons_on_dashboard(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $this->assertNotNull($waiter);

        $response = $this->actingAs($waiter)->get(route('dashboard'));

        $response->assertStatus(200);

        // Waiter should not see Admin Panel links or buttons
        $response->assertDontSee('System Settings');
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('roles.index'));
        $response->assertDontSee(route('branches.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('admin.stations.index'));
    }

    public function test_user_without_table_manage_permission_is_forbidden_from_tables_management(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $this->assertNotNull($waiter);

        $response = $this->actingAs($waiter)->get(route('tables.index'));

        // Waiter does not have pos.tables.manage, should be forbidden
        $response->assertStatus(403);
    }

    public function test_user_without_inventory_manage_permission_does_not_see_adjust_buttons(): void
    {
        // Create an inventory viewer with only inventory.view permission
        $viewerRole = Role::create([
            'name' => 'Stock Viewer',
            'slug' => 'stock-viewer',
            'is_system' => false,
        ]);
        $branch = Branch::first();

        $viewPerm = Permission::where('slug', 'inventory.view')->first();
        $viewerRole->permissions()->attach($viewPerm);

        $viewer = User::create([
            'name' => 'Inventory Viewer',
            'username' => 'inv_viewer',
            'email' => 'viewer@kerefa.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $viewer->roles()->attach($viewerRole);

        $response = $this->actingAs($viewer)->get(route('inventory.index'));

        $response->assertStatus(200);
        // Should NOT see the Adjust button
        $response->assertDontSee('data-bs-target="#adjustModal', false);
    }

    public function test_super_admin_sees_all_management_buttons_and_modules(): void
    {
        $superAdmin = User::where('username', 'admin')->first();
        $this->assertNotNull($superAdmin);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Launch POS Terminal');
        $response->assertSee('Active Restaurant Modules');
        $response->assertSee('Recent System Audit Trail');
    }
}
