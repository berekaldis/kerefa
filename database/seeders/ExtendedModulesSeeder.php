<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\EmployeeAttendance;
use App\Models\Expense;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\PurchaseOrder;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExtendedModulesSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('code', 'MAIN')->first() ?? Branch::first();
        $admin = User::where('username', 'admin')->first() ?? User::first();

        // 1. Inventory Categories
        $categories = [
            ['code' => 'MEAT', 'name' => 'Meat & Poultry', 'description' => 'Fresh chicken, beef, lamb'],
            ['code' => 'SPICE', 'name' => 'Spices & Dry Goods', 'description' => 'Berbere, mitmita, korerima, teff'],
            ['code' => 'BEV', 'name' => 'Beverages & Dairy', 'description' => 'Milk, coffee beans, sodas'],
            ['code' => 'PROD', 'name' => 'Fresh Vegetables & Produce', 'description' => 'Onions, garlic, ginger, tomatoes'],
        ];

        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[$cat['code']] = InventoryCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }

        // 2. Inventory Items
        $items = [
            ['code' => 'ING-CHK', 'category_id' => $catMap['MEAT']->id, 'name' => 'Fresh Whole Chicken (Doro)', 'unit' => 'kg', 'current_stock' => 45.0, 'min_stock_level' => 15.0, 'unit_cost' => 380.00],
            ['code' => 'ING-BEEF', 'category_id' => $catMap['MEAT']->id, 'name' => 'Prime Minced Beef', 'unit' => 'kg', 'current_stock' => 30.0, 'min_stock_level' => 10.0, 'unit_cost' => 650.00],
            ['code' => 'ING-BER', 'category_id' => $catMap['SPICE']->id, 'name' => 'Special Traditional Berbere', 'unit' => 'kg', 'current_stock' => 25.0, 'min_stock_level' => 5.0, 'unit_cost' => 420.00],
            ['code' => 'ING-ONN', 'category_id' => $catMap['PROD']->id, 'name' => 'Red Habesha Onions', 'unit' => 'kg', 'current_stock' => 80.0, 'min_stock_level' => 20.0, 'unit_cost' => 95.00],
            ['code' => 'ING-CFB', 'category_id' => $catMap['BEV']->id, 'name' => 'Yirgacheffe Coffee Beans', 'unit' => 'kg', 'current_stock' => 20.0, 'min_stock_level' => 6.0, 'unit_cost' => 450.00],
            ['code' => 'ING-OIL', 'category_id' => $catMap['SPICE']->id, 'name' => 'Pure Cooking Oil', 'unit' => 'liter', 'current_stock' => 40.0, 'min_stock_level' => 10.0, 'unit_cost' => 210.00],
        ];

        $itemMap = [];
        foreach ($items as $item) {
            $created = InventoryItem::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['branch_id' => $branch?->id])
            );
            $itemMap[$item['code']] = $created;

            // Initial Stock Ledger Entry
            StockTransaction::firstOrCreate(
                ['inventory_item_id' => $created->id, 'type' => 'in'],
                [
                    'branch_id' => $branch?->id,
                    'quantity' => $created->current_stock,
                    'unit_cost' => $created->unit_cost,
                    'total_cost' => $created->current_stock * $created->unit_cost,
                    'user_id' => $admin?->id,
                    'notes' => 'Initial operational stock balance',
                ]
            );
        }

        // 3. Suppliers
        $suppliers = [
            ['name' => 'Bole Fresh Poultry & Meat', 'contact_person' => 'Ato Dawit', 'phone' => '+251 91 200 1122', 'email' => 'dawit@boleproduce.et', 'address' => 'Bole Medhanealem, Addis Ababa'],
            ['name' => 'Merkato Traditional Spice Market', 'contact_person' => 'W/ro Roman', 'phone' => '+251 91 300 4455', 'email' => 'roman@merkatospice.et', 'address' => 'Merkato, Addis Ababa'],
            ['name' => 'Yirgacheffe Coffee Farmers Coop', 'contact_person' => 'Ato Girma', 'phone' => '+251 91 400 7788', 'email' => 'info@yirgacoffee.et', 'address' => 'Sidama / Addis Ababa Hub'],
        ];

        $supMap = [];
        foreach ($suppliers as $sup) {
            $supMap[] = Supplier::updateOrCreate(['name' => $sup['name']], $sup);
        }

        // 4. Sample Purchase Orders
        PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO-202611-0001'],
            [
                'branch_id' => $branch?->id,
                'supplier_id' => $supMap[0]->id,
                'status' => 'received',
                'total_amount' => 17100.00,
                'created_by' => $admin?->id,
                'notes' => 'Weekly poultry delivery for main kitchen',
            ]
        );

        // 5. Recipes for Menu Items
        $doroWatItem = MenuItem::where('code', 'DOR-01')->first();
        if ($doroWatItem && isset($itemMap['ING-CHK'], $itemMap['ING-BER'], $itemMap['ING-ONN'])) {
            $recipe = Recipe::updateOrCreate(
                ['menu_item_id' => $doroWatItem->id],
                [
                    'name' => 'Authentic Doro Wat Recipe',
                    'yield_portions' => 1,
                    'total_cost' => 145.00,
                    'cost_per_portion' => 145.00,
                    'food_cost_percentage' => 32.22,
                    'instructions' => 'Slow-cook onions without oil for 45 mins. Add berbere, simmer. Add chicken and boiled eggs.',
                ]
            );

            RecipeIngredient::updateOrCreate(
                ['recipe_id' => $recipe->id, 'inventory_item_id' => $itemMap['ING-CHK']->id],
                ['quantity' => 0.25, 'unit' => 'kg', 'unit_cost' => 380.00, 'subtotal_cost' => 95.00]
            );
            RecipeIngredient::updateOrCreate(
                ['recipe_id' => $recipe->id, 'inventory_item_id' => $itemMap['ING-BER']->id],
                ['quantity' => 0.05, 'unit' => 'kg', 'unit_cost' => 420.00, 'subtotal_cost' => 21.00]
            );
            RecipeIngredient::updateOrCreate(
                ['recipe_id' => $recipe->id, 'inventory_item_id' => $itemMap['ING-ONN']->id],
                ['quantity' => 0.30, 'unit' => 'kg', 'unit_cost' => 95.00, 'subtotal_cost' => 28.50]
            );
        }

        // 6. Operating Expenses (Finance)
        $expenses = [
            ['category' => 'utilities', 'title' => 'Electric Power & Generator Diesel', 'amount' => 8500.00, 'date' => now()->toDateString(), 'payment_method' => 'cbe_birr', 'notes' => 'Monthly utility bill'],
            ['category' => 'supplies', 'title' => 'Packaging Materials & Thermal Paper (IRP 200D 80mm)', 'amount' => 3200.00, 'date' => now()->toDateString(), 'payment_method' => 'telebirr', 'notes' => '10 boxes 80mm paper rolls'],
            ['category' => 'maintenance', 'title' => 'Central Bar Espresso Machine Servicing', 'amount' => 4500.00, 'date' => now()->subDays(2)->toDateString(), 'payment_method' => 'cash', 'notes' => 'Water filter & gasket replacement'],
        ];

        foreach ($expenses as $exp) {
            Expense::updateOrCreate(
                ['title' => $exp['title'], 'date' => $exp['date']],
                array_merge($exp, ['branch_id' => $branch?->id, 'recorded_by' => $admin?->id])
            );
        }

        // 7. Employee Attendance (HR)
        $staffUsers = User::where('is_active', true)->take(8)->get();
        foreach ($staffUsers as $stf) {
            EmployeeAttendance::updateOrCreate(
                ['user_id' => $stf->id, 'date' => now()->toDateString()],
                [
                    'check_in' => '07:45:00',
                    'check_out' => null,
                    'status' => 'present',
                    'notes' => 'On duty - regular morning shift',
                ]
            );
        }
    }
}
