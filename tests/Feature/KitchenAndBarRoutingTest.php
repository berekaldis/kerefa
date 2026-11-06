<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\OrderRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenAndBarRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_kitchen_staff_can_view_kds_and_update_ticket_status(): void
    {
        $chef = User::where('username', 'chef')->first();
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'T2')->first();
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

        $kitchenTicket = $order->tickets()->where('department', 'kitchen')->first();
        $this->assertEquals('queued', $kitchenTicket->status);

        // Chef views KDS
        $response = $this->actingAs($chef)->get(route('kds.index'));
        $response->assertStatus(200);
        $response->assertSee('Kitchen Display System');
        $response->assertSee($kitchenTicket->ticket_number);

        // Chef updates ticket to preparing
        $response = $this->actingAs($chef)->post(route('kds.ticket.status', $kitchenTicket), [
            'status' => 'preparing',
        ]);
        $response->assertRedirect();
        $kitchenTicket->refresh();
        $this->assertEquals('preparing', $kitchenTicket->status);
        $this->assertNotNull($kitchenTicket->started_at);

        // Chef marks ready
        $response = $this->actingAs($chef)->post(route('kds.ticket.status', $kitchenTicket), [
            'status' => 'ready',
        ]);
        $kitchenTicket->refresh();
        $this->assertEquals('ready', $kitchenTicket->status);
        $this->assertNotNull($kitchenTicket->ready_at);

        // Order status should now reflect ready
        $order->refresh();
        $this->assertEquals('ready', $order->status);
    }

    public function test_bar_staff_can_view_bds_and_update_ticket_status(): void
    {
        $bartender = User::where('username', 'bartender')->first();
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'B1')->first();
        $drinkItem = MenuItem::where('code', 'JUC-01')->first();

        $orderService = app(OrderRoutingService::class);
        $order = $orderService->createOrder(
            [
                'branch_id' => $table->branch_id,
                'order_type' => 'dine_in',
                'restaurant_table_id' => $table->id,
            ],
            [
                [
                    'menu_item_id' => $drinkItem->id,
                    'quantity' => 2,
                ],
            ],
            $waiter
        );

        $barTicket = $order->tickets()->where('department', 'bar')->first();
        $this->assertEquals('queued', $barTicket->status);

        // Bartender views BDS
        $response = $this->actingAs($bartender)->get(route('bds.index'));
        $response->assertStatus(200);
        $response->assertSee('Bar & Beverage Display', false);
        $response->assertSee($barTicket->ticket_number);

        // Bartender marks ready
        $this->actingAs($bartender)->post(route('bds.ticket.status', $barTicket), [
            'status' => 'ready',
        ]);
        $barTicket->refresh();
        $this->assertEquals('ready', $barTicket->status);
    }
}
