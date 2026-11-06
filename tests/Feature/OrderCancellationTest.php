<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\OrderRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_authorized_user_can_cancel_order_with_reason(): void
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

        $table->refresh();
        $this->assertEquals('occupied', $table->status);

        $response = $this->actingAs($admin)->post(route('orders.cancel', $order), [
            'reason' => 'Guest had an emergency and left before preparation completed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order->refresh();
        $table->refresh();

        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('available', $table->status);
        $this->assertEquals('Guest had an emergency and left before preparation completed', $order->cancellation_reason);
        $this->assertEquals($admin->id, $order->cancelled_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cancel_order',
            'module' => 'pos',
            'record_id' => $order->id,
        ]);
    }
}
