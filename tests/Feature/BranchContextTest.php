<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_super_admin_can_switch_to_another_active_branch(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();

        $branch2 = Branch::create([
            'code' => 'CMC',
            'name' => 'Kerefa Restaurant - CMC Branch',
            'currency' => 'ETB',
            'is_main' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('branches.switch'), [
            'branch_id' => $branch2->id,
        ]);

        $response->assertSessionHas('kerefa_active_branch_id', $branch2->id);
        $response->assertSessionHas('success');
    }

    public function test_staff_cannot_switch_to_unauthorized_branch(): void
    {
        $staff = User::where('username', 'cashier')->first();

        $restrictedBranch = Branch::create([
            'code' => 'HAW',
            'name' => 'Kerefa Restaurant - Hawassa Branch',
            'currency' => 'ETB',
            'is_main' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->post(route('branches.switch'), [
            'branch_id' => $restrictedBranch->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertNotEquals($restrictedBranch->id, session('kerefa_active_branch_id'));
    }
}
