<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\OrderRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_order_can_be_transferred_to_another_table(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $bm = User::where('username', 'gm')->first();

        $tableFrom = RestaurantTable::where('table_number', 'T3')->first();
        $tableTo = RestaurantTable::where('table_number', 'T4')->first();

        $foodItem = MenuItem::where('code', 'DOR-01')->first();

        $orderService = app(OrderRoutingService::class);
        $order = $orderService->createOrder(
            [
                'branch_id' => $tableFrom->branch_id,
                'order_type' => 'dine_in',
                'restaurant_table_id' => $tableFrom->id,
            ],
            [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 1,
                ],
            ],
            $waiter
        );

        $tableFrom->refresh();
        $this->assertEquals('occupied', $tableFrom->status);
        $this->assertEquals('available', $tableTo->status);

        // Transfer order from T3 to T4
        $response = $this->actingAs($bm)->post(route('tables.transfer', $tableFrom), [
            'target_table_id' => $tableTo->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tableFrom->refresh();
        $tableTo->refresh();
        $order->refresh();

        $this->assertEquals('available', $tableFrom->status);
        $this->assertEquals('occupied', $tableTo->status);
        $this->assertEquals($tableTo->id, $order->restaurant_table_id);
    }

    public function test_table_merge_operation(): void
    {
        $bm = User::where('username', 'gm')->first();
        $table1 = RestaurantTable::where('table_number', 'T5')->first();
        $table2 = RestaurantTable::where('table_number', 'T6')->first();

        $response = $this->actingAs($bm)->post(route('tables.merge', $table1), [
            'target_table_id' => $table2->id,
        ]);

        $response->assertRedirect();
        $table1->refresh();

        $this->assertEquals($table2->id, $table1->merged_with_table_id);
        $this->assertEquals('occupied', $table1->status);
    }
}
