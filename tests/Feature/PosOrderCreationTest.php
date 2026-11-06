<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_waiter_can_view_pos_terminal(): void
    {
        $waiter = User::where('username', 'waiter')->first();

        $response = $this->actingAs($waiter)->get(route('pos.terminal'));

        $response->assertStatus(200);
        $response->assertSee('POS Order Terminal');
        $response->assertSee('Doro Wat');
        $response->assertSee('Traditional Jebena Buna');
    }

    public function test_dine_in_order_creates_split_tickets_and_occupies_table(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'T1')->first();
        $this->assertEquals('available', $table->status);

        $foodItem = MenuItem::where('code', 'DOR-01')->first(); // Kitchen
        $drinkItem = MenuItem::where('code', 'CFE-01')->first(); // Bar

        $response = $this->actingAs($waiter)->post(route('pos.orders.store'), [
            'order_type' => 'dine_in',
            'restaurant_table_id' => $table->id,
            'notes' => 'Test POS order',
            'items' => [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 2,
                    'special_instructions' => 'Extra spicy',
                ],
                [
                    'menu_item_id' => $drinkItem->id,
                    'quantity' => 2,
                    'special_instructions' => 'With tena adam',
                ],
            ],
        ]);

        $response->assertRedirect();

        // Verify order exists
        $order = Order::where('restaurant_table_id', $table->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('dine_in', $order->order_type);
        $this->assertEquals('in_preparation', $order->status);

        // Verify Table status updated to occupied
        $table->refresh();
        $this->assertEquals('occupied', $table->status);

        // Verify separate tickets created for Kitchen and Bar
        $this->assertCount(2, $order->tickets);

        $kitchenTicket = $order->tickets()->where('department', 'kitchen')->first();
        $this->assertNotNull($kitchenTicket);
        $this->assertCount(1, $kitchenTicket->items);
        $this->assertEquals('queued', $kitchenTicket->status);
        $this->assertEquals($foodItem->id, $kitchenTicket->items->first()->orderItem->menu_item_id);

        $barTicket = $order->tickets()->where('department', 'bar')->first();
        $this->assertNotNull($barTicket);
        $this->assertCount(1, $barTicket->items);
        $this->assertEquals('queued', $barTicket->status);
        $this->assertEquals($drinkItem->id, $barTicket->items->first()->orderItem->menu_item_id);

        // Verify status history
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status_to' => 'in_preparation',
        ]);
    }

    public function test_takeaway_order_routes_properly_without_table(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $foodItem = MenuItem::where('code', 'SAM-01')->first();

        $response = $this->actingAs($waiter)->post(route('pos.orders.store'), [
            'order_type' => 'takeaway',
            'customer_name' => 'Dawit Yilma',
            'customer_phone' => '+251911000099',
            'items' => [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response->assertRedirect();

        $order = Order::where('customer_name', 'Dawit Yilma')->first();
        $this->assertNotNull($order);
        $this->assertEquals('takeaway', $order->order_type);
        $this->assertNull($order->restaurant_table_id);
    }
}
