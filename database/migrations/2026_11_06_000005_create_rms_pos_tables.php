<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dining Areas
        Schema::create('dining_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        // 2. Restaurant Tables
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('dining_area_id')->constrained('dining_areas')->cascadeOnDelete();
            $table->string('table_number', 30);
            $table->integer('capacity')->default(4);
            $table->string('status', 30)->default('available'); // available, occupied, reserved, maintenance
            $table->foreignId('merged_with_table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'table_number']);
            $table->index(['branch_id', 'status']);
        });

        // 3. Menu Categories
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        // 4. Menu Items
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('category_id')->constrained('menu_categories')->cascadeOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 14, 4);
            $table->decimal('cost_price', 14, 4)->nullable();
            $table->string('preparation_department', 30)->default('kitchen'); // kitchen, bar
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('recipe_enabled')->default(false);
            $table->timestamps();

            $table->index(['branch_id', 'is_active', 'is_available']);
            $table->index('preparation_department');
        });

        // 5. Menu Item Modifiers
        Schema::create('menu_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('additional_price', 14, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->string('order_type', 30)->default('dine_in'); // dine_in, takeaway, delivery
            $table->foreignId('restaurant_table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->text('delivery_address')->nullable();
            $table->foreignId('waiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending'); // pending, in_preparation, partially_ready, ready, served, completed, cancelled
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount_amount', 14, 4)->default(0);
            $table->decimal('tax_amount', 14, 4)->default(0);
            $table->decimal('service_charge_amount', 14, 4)->default(0);
            $table->decimal('total_amount', 14, 4)->default(0);
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'order_type']);
            $table->index('created_at');
        });

        // 7. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('subtotal', 14, 4);
            $table->text('special_instructions')->nullable();
            $table->string('status', 30)->default('sent'); // sent, in_preparation, ready, served, cancelled
            $table->string('preparation_department', 30)->default('kitchen'); // kitchen, bar
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'preparation_department']);
            $table->index('status');
        });

        // 8. Order Item Modifiers (Pivot)
        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('menu_item_modifier_id')->nullable()->constrained('menu_item_modifiers')->nullOnDelete();
            $table->string('name', 100);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->timestamps();
        });

        // 9. Kitchen & Bar Tickets
        Schema::create('kitchen_bar_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('ticket_number', 50);
            $table->string('department', 30); // kitchen, bar
            $table->string('status', 30)->default('queued'); // queued, preparing, ready, collected
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->integer('reprint_count')->default(0);
            $table->timestamps();

            $table->index(['branch_id', 'department', 'status']);
        });

        // 10. Ticket Items
        Schema::create('ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('kitchen_bar_tickets')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->text('special_instructions')->nullable();
            $table->string('status', 30)->default('queued'); // queued, preparing, ready, collected
            $table->timestamps();
        });

        // 11. Order Status History
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30);
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('comments', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('ticket_items');
        Schema::dropIfExists('kitchen_bar_tickets');
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_item_modifiers');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('dining_areas');
    }
};
