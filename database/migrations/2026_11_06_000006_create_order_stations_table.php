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
        // 1. Order Printing / Preparation Stations
        Schema::create('order_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('station_type', 30)->default('kitchen'); // kitchen, bar, grill, cafe
            $table->string('printer_model', 100)->default('IRP 200D');
            $table->string('printer_connection', 50)->default('thermal_80mm'); // thermal_80mm, network_ip, usb_spooler
            $table->string('printer_ip', 100)->nullable();
            $table->integer('printer_port')->default(9100);
            $table->integer('paper_width_mm')->default(80);
            $table->boolean('auto_print')->default(true);
            $table->integer('print_copies')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'station_type', 'is_active']);
        });

        // 2. Add order_station_id to menu_categories
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->foreignId('order_station_id')->nullable()->constrained('order_stations')->nullOnDelete();
        });

        // 3. Add order_station_id to menu_items
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('order_station_id')->nullable()->constrained('order_stations')->nullOnDelete();
        });

        // 4. Add order_station_id to kitchen_bar_tickets
        Schema::table('kitchen_bar_tickets', function (Blueprint $table) {
            $table->foreignId('order_station_id')->nullable()->constrained('order_stations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kitchen_bar_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_station_id');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_station_id');
        });

        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_station_id');
        });

        Schema::dropIfExists('order_stations');
    }
};
