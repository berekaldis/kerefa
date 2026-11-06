<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_super_admin_can_view_settings(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('System Configuration');
        $response->assertSee('Customer Bill Generation & Receipt Printing is strictly disabled', false);
    }

    public function test_bill_generation_cannot_be_activated_in_initial_release(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'app_name' => 'Kerefa ERP',
            'bill_generation_enabled' => '1',
        ]);

        $response->assertSessionHas('error', 'Customer bill generation is not authorized for activation in this release.');

        // Verify setting in database remains 0
        $this->assertEquals('0', SystemSetting::whereNull('branch_id')->where('key', 'bill_generation_enabled')->value('value'));
    }

    public function test_admin_can_update_general_settings(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'app_name' => 'Kerefa Restaurant & Lounge ERP',
            'currency_symbol' => 'Br',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('Kerefa Restaurant & Lounge ERP', SystemSetting::whereNull('branch_id')->where('key', 'app_name')->value('value'));
    }
}
