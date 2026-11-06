<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\OrderRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoBillingEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_pos_terminal_does_not_render_bill_generation_or_print_bill_actions(): void
    {
        $waiter = User::where('username', 'waiter')->first();

        $response = $this->actingAs($waiter)->get(route('pos.terminal'));

        $response->assertStatus(200);
        $response->assertDontSee('Generate Bill');
        $response->assertDontSee('Print Bill');
        $response->assertDontSee('Print Receipt');
        $response->assertDontSee('Checkout Invoice');
    }

    public function test_order_show_screen_does_not_render_customer_bill_actions(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'T1')->first();
        $foodItem = MenuItem::where('code', 'DOR-01')->first();

        $orderService = app(OrderRoutingService::class);
        $order = $orderService->createOrder(
            [
                'branch_id' => $table->branch_id,
                'order_type' => 'dine_in',
                'restaurant_table_id' => $table->id,
            ],
            [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 1,
                ],
            ],
            $waiter
        );

        $response = $this->actingAs($admin)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('Generate Bill');
        $response->assertDontSee('Print Bill');
        $response->assertDontSee('Print Receipt');
        $response->assertSee('Customer billing & checkout invoice generation is not active', false);
    }

    public function test_system_settings_strictly_disables_bill_generation(): void
    {
        $billSetting = SystemSetting::whereNull('branch_id')->where('key', 'bill_generation_enabled')->value('value');
        $this->assertEquals('0', $billSetting);
    }
}
