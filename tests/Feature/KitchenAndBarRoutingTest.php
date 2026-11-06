<?php

namespace Tests\Feature;

use App\Models\MenuItem;
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

    public function test_order_creates_separate_tickets_mapped_to_irp200d_order_stations(): void
    {
        $waiter = User::where('username', 'waiter')->first();
        $table = RestaurantTable::where('table_number', 'T1')->first();

        $foodItem = MenuItem::where('code', 'DOR-01')->first();
        $drinkItem = MenuItem::where('code', 'CFE-01')->first();

        $orderService = app(OrderRoutingService::class);
        $order = $orderService->createOrder(
            [
                'branch_id' => $table->branch_id,
                'order_type' => 'dine_in',
                'restaurant_table_id' => $table->id,
                'notes' => 'Urgent guest',
            ],
            [
                [
                    'menu_item_id' => $foodItem->id,
                    'quantity' => 2,
                    'special_instructions' => 'Extra spicy please',
                ],
                [
                    'menu_item_id' => $drinkItem->id,
                    'quantity' => 3,
                    'special_instructions' => 'Traditional clay cup',
                ],
            ],
            $waiter
        );

        $this->assertEquals(2, $order->tickets()->count());

        $kitchenTicket = $order->tickets()->where('department', 'kitchen')->first();
        $barTicket = $order->tickets()->where('department', 'bar')->first();

        $this->assertNotNull($kitchenTicket);
        $this->assertNotNull($barTicket);

        // Verify Station and Printer Model (IRP 200D)
        $this->assertNotNull($kitchenTicket->order_station_id);
        $this->assertEquals('IRP 200D', $kitchenTicket->station?->printer_model);
        $this->assertEquals('kitchen', $kitchenTicket->station?->station_type);

        $this->assertNotNull($barTicket->order_station_id);
        $this->assertEquals('IRP 200D', $barTicket->station?->printer_model);
        $this->assertEquals('bar', $barTicket->station?->station_type);

        // Verify items separation
        $this->assertEquals(1, $kitchenTicket->items()->count());
        $this->assertEquals(2, (float) $kitchenTicket->items()->first()->quantity);
        $this->assertEquals('Extra spicy please', $kitchenTicket->items()->first()->special_instructions);

        $this->assertEquals(1, $barTicket->items()->count());
        $this->assertEquals(3, (float) $barTicket->items()->first()->quantity);
        $this->assertEquals('Traditional clay cup', $barTicket->items()->first()->special_instructions);
    }

    public function test_admin_can_view_station_mappings_and_configure_printers(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.stations.index'));
        $response->assertStatus(200);
        $response->assertSee('Station Mapping');
        $response->assertSee('Main Kitchen Station');
        $response->assertSee('Central Bar Station');
        $response->assertSee('IRP 200D');
    }

    public function test_admin_can_create_new_order_station_with_printer(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.stations.store'), [
            'name' => 'Outdoor Grill Station',
            'code' => 'GRILL_01',
            'station_type' => 'grill',
            'printer_model' => 'IRP 200D',
            'printer_connection' => 'thermal_80mm',
            'paper_width_mm' => 80,
            'auto_print' => 1,
            'print_copies' => 1,
            'notes' => 'Dedicated terrace grill station',
        ]);

        $response->assertRedirect(route('admin.stations.index'));
        $this->assertDatabaseHas('order_stations', [
            'code' => 'GRILL_01',
            'printer_model' => 'IRP 200D',
        ]);
    }
}
