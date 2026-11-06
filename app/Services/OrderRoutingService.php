<?php

namespace App\Services;

use App\Models\KitchenBarTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\OrderStation;
use App\Models\OrderStatusHistory;
use App\Models\RestaurantTable;
use App\Models\SystemSetting;
use App\Models\TicketItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderRoutingService
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Create an order with automatic Kitchen & Bar split routing.
     *
     * @param  array<string, mixed>  $orderData
     * @param  array<int, array<string, mixed>>  $itemsData
     */
    public function createOrder(array $orderData, array $itemsData, User $waiter): Order
    {
        return DB::transaction(function () use ($orderData, $itemsData, $waiter) {
            $branchId = $orderData['branch_id'];
            $orderType = $orderData['order_type'] ?? 'dine_in';
            $tableId = $orderData['restaurant_table_id'] ?? null;

            // Table validation for dine-in
            $table = null;
            if ($orderType === 'dine_in') {
                if (! $tableId) {
                    throw ValidationException::withMessages([
                        'restaurant_table_id' => [__('A table must be selected for dine-in orders.')],
                    ]);
                }

                $table = RestaurantTable::where('id', $tableId)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($table->status === 'maintenance') {
                    throw ValidationException::withMessages([
                        'restaurant_table_id' => [__('Table :number is currently under maintenance.', ['number' => $table->table_number])],
                    ]);
                }
            }

            // Generate unique Order Number
            $datePrefix = date('Ymd');
            $countToday = Order::where('branch_id', $branchId)
                ->whereDate('created_at', today())
                ->count() + 1;
            $orderNumber = sprintf('ORD-%s-%s-%04d', $branchId, $datePrefix, $countToday);

            // Create Order
            $order = Order::create([
                'branch_id' => $branchId,
                'order_number' => $orderNumber,
                'order_type' => $orderType,
                'restaurant_table_id' => $tableId,
                'customer_name' => $orderData['customer_name'] ?? null,
                'customer_phone' => $orderData['customer_phone'] ?? null,
                'delivery_address' => $orderData['delivery_address'] ?? null,
                'waiter_id' => $waiter->id,
                'status' => 'in_preparation',
                'notes' => $orderData['notes'] ?? null,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'service_charge_amount' => 0,
                'total_amount' => 0,
            ]);

            // Mark table occupied
            if ($table) {
                $table->status = 'occupied';
                $table->save();
            }

            // Process Items
            $subtotal = 0;
            $kitchenItems = [];
            $barItems = [];

            foreach ($itemsData as $row) {
                $menuItem = MenuItem::findOrFail($row['menu_item_id']);
                $qty = (float) ($row['quantity'] ?? 1);
                $unitPrice = (float) $menuItem->price;
                $itemSubtotal = $unitPrice * $qty;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'special_instructions' => $row['special_instructions'] ?? null,
                    'status' => 'sent',
                    'preparation_department' => $menuItem->preparation_department,
                ]);

                // Modifiers if any
                if (! empty($row['modifiers'])) {
                    foreach ($row['modifiers'] as $modId) {
                        $modifier = $menuItem->modifiers()->find($modId);
                        if ($modifier) {
                            OrderItemModifier::create([
                                'order_item_id' => $orderItem->id,
                                'menu_item_modifier_id' => $modifier->id,
                                'name' => $modifier->name,
                                'unit_price' => $modifier->additional_price,
                            ]);
                            $modCost = ((float) $modifier->additional_price) * $qty;
                            $itemSubtotal += $modCost;
                        }
                    }
                    $orderItem->subtotal = $itemSubtotal;
                    $orderItem->save();
                }

                $subtotal += $itemSubtotal;

                if ($menuItem->preparation_department === 'bar') {
                    $barItems[] = $orderItem;
                } else {
                    $kitchenItems[] = $orderItem;
                }
            }

            // Total calculations (Service charge & VAT reference if configured, without billing generation)
            $serviceChargeRate = (float) SystemSetting::get('service_charge_rate', 10.00, $branchId);
            $vatRate = (float) SystemSetting::get('vat_rate', 15.00, $branchId);

            $serviceCharge = $orderType === 'dine_in' ? ($subtotal * ($serviceChargeRate / 100)) : 0;
            $taxAmount = ($subtotal + $serviceCharge) * ($vatRate / 100);
            $totalAmount = $subtotal + $serviceCharge + $taxAmount;

            $order->update([
                'subtotal' => $subtotal,
                'service_charge_amount' => $serviceCharge,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            // Resolve Destination Stations for Thermal Printing (IRP 200D)
            $kitchenStation = OrderStation::where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })->where('station_type', 'kitchen')->where('is_active', true)->first();

            $barStation = OrderStation::where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })->where('station_type', 'bar')->where('is_active', true)->first();

            // Automatic Kitchen Ticket Routing
            if (! empty($kitchenItems)) {
                $kitchenTicket = KitchenBarTicket::create([
                    'branch_id' => $branchId,
                    'order_id' => $order->id,
                    'order_station_id' => $kitchenStation?->id,
                    'ticket_number' => sprintf('KTK-%s-%04d', $order->id, count($order->tickets) + 1),
                    'department' => 'kitchen',
                    'status' => 'queued',
                    'sent_at' => now(),
                ]);

                foreach ($kitchenItems as $item) {
                    TicketItem::create([
                        'ticket_id' => $kitchenTicket->id,
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'special_instructions' => $item->special_instructions,
                        'status' => 'queued',
                    ]);
                }
            }

            // Automatic Bar Ticket Routing
            if (! empty($barItems)) {
                $barTicket = KitchenBarTicket::create([
                    'branch_id' => $branchId,
                    'order_id' => $order->id,
                    'order_station_id' => $barStation?->id,
                    'ticket_number' => sprintf('BTK-%s-%04d', $order->id, count($order->tickets) + 1),
                    'department' => 'bar',
                    'status' => 'queued',
                    'sent_at' => now(),
                ]);

                foreach ($barItems as $item) {
                    TicketItem::create([
                        'ticket_id' => $barTicket->id,
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'special_instructions' => $item->special_instructions,
                        'status' => 'queued',
                    ]);
                }
            }

            // Record status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status_from' => 'pending',
                'status_to' => 'in_preparation',
                'changed_by' => $waiter->id,
                'comments' => 'Order created and routed to preparation departments',
                'created_at' => now(),
            ]);

            // Audit log
            $this->auditLog->log(
                action: 'create_order',
                module: 'pos',
                description: "Order {$order->order_number} created by {$waiter->name}",
                record: $order,
                newValues: [
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'items_count' => count($itemsData),
                    'kitchen_tickets' => ! empty($kitchenItems) ? 1 : 0,
                    'bar_tickets' => ! empty($barItems) ? 1 : 0,
                ],
                branchId: $branchId
            );

            return $order;
        });
    }

    /**
     * Update Kitchen or Bar Ticket status.
     */
    public function updateTicketStatus(KitchenBarTicket $ticket, string $newStatus, User $user): KitchenBarTicket
    {
        return DB::transaction(function () use ($ticket, $newStatus, $user) {
            $oldStatus = $ticket->status;
            $ticket->status = $newStatus;

            if ($newStatus === 'preparing' && ! $ticket->started_at) {
                $ticket->started_at = now();
            } elseif ($newStatus === 'ready' && ! $ticket->ready_at) {
                $ticket->ready_at = now();
            } elseif ($newStatus === 'collected' && ! $ticket->collected_at) {
                $ticket->collected_at = now();
            }

            $ticket->save();

            // Update individual ticket items
            $ticket->items()->update(['status' => $newStatus]);

            // Recalculate parent order status
            $order = $ticket->order;
            $allTickets = $order->tickets;
            $allReady = $allTickets->every(fn ($t) => in_array($t->status, ['ready', 'collected']));
            $anyReady = $allTickets->contains(fn ($t) => in_array($t->status, ['ready', 'collected']));

            $orderStatusTo = $order->status;
            if ($allReady) {
                $orderStatusTo = 'ready';
            } elseif ($anyReady) {
                $orderStatusTo = 'partially_ready';
            }

            if ($orderStatusTo !== $order->status) {
                $prevOrderStatus = $order->status;
                $order->status = $orderStatusTo;
                $order->save();

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status_from' => $prevOrderStatus,
                    'status_to' => $orderStatusTo,
                    'changed_by' => $user->id,
                    'comments' => "Ticket {$ticket->ticket_number} marked as {$newStatus}",
                    'created_at' => now(),
                ]);
            }

            $this->auditLog->log(
                action: 'update_ticket_status',
                module: 'pos',
                description: "Ticket {$ticket->ticket_number} ({$ticket->department}) marked as {$newStatus}",
                record: $ticket,
                branchId: $ticket->branch_id
            );

            return $ticket;
        });
    }

    /**
     * Cancel an open order with authorization.
     */
    public function cancelOrder(Order $order, User $user, string $reason): Order
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $prevStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason,
            ]);

            // Free table if dine in
            if ($order->restaurant_table_id) {
                $table = $order->table;
                if ($table && $table->status === 'occupied') {
                    $table->status = 'available';
                    $table->save();
                }
            }

            // Cancel tickets
            $order->tickets()->update(['status' => 'collected']);
            $order->items()->update(['status' => 'cancelled']);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status_from' => $prevStatus,
                'status_to' => 'cancelled',
                'changed_by' => $user->id,
                'comments' => "Cancelled by {$user->name}. Reason: {$reason}",
                'created_at' => now(),
            ]);

            $this->auditLog->log(
                action: 'cancel_order',
                module: 'pos',
                description: "Order {$order->order_number} cancelled by {$user->name}. Reason: {$reason}",
                record: $order,
                branchId: $order->branch_id
            );

            return $order;
        });
    }

    /**
     * Transfer an order to another table.
     */
    public function transferTable(Order $order, RestaurantTable $newTable, User $user): Order
    {
        return DB::transaction(function () use ($order, $newTable, $user) {
            $oldTable = $order->table;

            if ($oldTable) {
                $oldTable->status = 'available';
                $oldTable->save();
            }

            $newTable->status = 'occupied';
            $newTable->save();

            $order->restaurant_table_id = $newTable->id;
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status_from' => $order->status,
                'status_to' => $order->status,
                'changed_by' => $user->id,
                'comments' => "Transferred from Table {$oldTable?->table_number} to Table {$newTable->table_number}",
                'created_at' => now(),
            ]);

            $this->auditLog->log(
                action: 'transfer_table',
                module: 'pos',
                description: "Transferred Order {$order->order_number} to Table {$newTable->table_number}",
                record: $order,
                branchId: $order->branch_id
            );

            return $order;
        });
    }

    /**
     * Merge source table into target table.
     */
    public function mergeTables(RestaurantTable $sourceTable, RestaurantTable $targetTable, User $user): void
    {
        DB::transaction(function () use ($sourceTable, $targetTable) {
            $sourceTable->merged_with_table_id = $targetTable->id;
            $sourceTable->status = 'occupied';
            $sourceTable->save();

            $this->auditLog->log(
                action: 'merge_tables',
                module: 'pos',
                description: "Table {$sourceTable->table_number} merged into Table {$targetTable->table_number}",
                record: $sourceTable,
                branchId: $sourceTable->branch_id
            );
        });
    }
}
