<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosOrderOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_can_add_products_to_held_order_and_route_supplementary_tickets(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'T1')->first();
        $foodItem = MenuItem::where('code', 'DOR-01')->first(); // Kitchen
        $drinkItem = MenuItem::where('code', 'CFE-01')->first(); // Bar

        // 1. Create initial order for Table T1
        $initialPayload = [
            'order_type' => 'dine_in',
            'restaurant_table_id' => $table->id,
            'waiter_id' => $waiter->id,
            'items' => [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 1,
                    'unit_price' => $foodItem->price,
                ],
            ],
        ];

        $createResponse = $this->actingAs($waiter)
            ->postJson(route('pos.orders.store'), $initialPayload);

        $createResponse->assertStatus(200);
        $orderId = $createResponse->json('order.id');
        $order = Order::find($orderId);

        $this->assertEquals('occupied', $table->fresh()->status);
        $this->assertCount(1, $order->tickets);

        // 2. Later, guests request drinks. Waiter adds products to held order and holds again
        $addItemsPayload = [
            'items' => [
                [
                    'menu_item_id' => $drinkItem->id,
                    'quantity' => 2,
                    'notes' => 'Extra sugar on side',
                ],
            ],
        ];

        $addResponse = $this->actingAs($waiter)
            ->postJson(route('pos.orders.add-items', $order), $addItemsPayload);

        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => true]);

        $order->refresh();
        $this->assertCount(2, $order->items);
        $this->assertCount(2, $order->tickets); // Initial Kitchen ticket + Supplementary Bar ticket
        $this->assertStringContainsString('BTK-ADD', $order->tickets->last()->ticket_number);
        $this->assertEquals('occupied', $table->fresh()->status);
    }

    public function test_can_settle_order_in_birr_and_release_dining_table(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'T2')->first();
        $foodItem = MenuItem::where('code', 'DOR-01')->first();

        // Create an open order
        $initialPayload = [
            'order_type' => 'dine_in',
            'restaurant_table_id' => $table->id,
            'waiter_id' => $waiter->id,
            'items' => [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 1,
                    'unit_price' => $foodItem->price,
                ],
            ],
        ];

        $createResponse = $this->actingAs($waiter)
            ->postJson(route('pos.orders.store'), $initialPayload);

        $orderId = $createResponse->json('order.id');
        $order = Order::find($orderId);

        $this->assertEquals('occupied', $table->fresh()->status);

        // Settle order via Telebirr in Ethiopian Birr
        $settleResponse = $this->actingAs($waiter)
            ->postJson(route('pos.orders.settle', $order), [
                'payment_method' => 'telebirr',
                'amount_tendered' => 500.00,
            ]);

        $settleResponse->assertStatus(200);
        $settleResponse->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertStringContainsString('TELEBIRR', $order->notes);
        $this->assertEquals('available', $table->fresh()->status);
    }
}
