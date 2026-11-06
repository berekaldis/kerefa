<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DiningArea;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemModifier;
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class RmsPosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'MAIN')->first();
        if (! $mainBranch) {
            return;
        }

        // 1. Dining Areas
        $areas = [
            ['name' => 'Main Dining Hall', 'description' => 'Primary indoor restaurant dining room', 'sort' => 1],
            ['name' => 'VIP Lounge', 'description' => 'Private executive dining area', 'sort' => 2],
            ['name' => 'Garden & Terrace', 'description' => 'Outdoor patio dining', 'sort' => 3],
            ['name' => 'Bar & Coffee Lounge', 'description' => 'Casual beverage and counter seating', 'sort' => 4],
        ];

        $areaModels = [];
        foreach ($areas as $a) {
            $areaModels[$a['name']] = DiningArea::updateOrCreate(
                ['branch_id' => $mainBranch->id, 'name' => $a['name']],
                [
                    'description' => $a['description'],
                    'sort_order' => $a['sort'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Tables
        $tables = [
            // Main Dining Hall
            ['area' => 'Main Dining Hall', 'num' => 'T1', 'cap' => 4],
            ['area' => 'Main Dining Hall', 'num' => 'T2', 'cap' => 4],
            ['area' => 'Main Dining Hall', 'num' => 'T3', 'cap' => 6],
            ['area' => 'Main Dining Hall', 'num' => 'T4', 'cap' => 4],
            ['area' => 'Main Dining Hall', 'num' => 'T5', 'cap' => 4],
            ['area' => 'Main Dining Hall', 'num' => 'T6', 'cap' => 6],
            // VIP Lounge
            ['area' => 'VIP Lounge', 'num' => 'VIP-1', 'cap' => 8],
            ['area' => 'VIP Lounge', 'num' => 'VIP-2', 'cap' => 10],
            // Garden & Terrace
            ['area' => 'Garden & Terrace', 'num' => 'G1', 'cap' => 4],
            ['area' => 'Garden & Terrace', 'num' => 'G2', 'cap' => 4],
            ['area' => 'Garden & Terrace', 'num' => 'G3', 'cap' => 6],
            // Bar & Coffee Lounge
            ['area' => 'Bar & Coffee Lounge', 'num' => 'B1', 'cap' => 2],
            ['area' => 'Bar & Coffee Lounge', 'num' => 'B2', 'cap' => 2],
            ['area' => 'Bar & Coffee Lounge', 'num' => 'B3', 'cap' => 4],
        ];

        foreach ($tables as $t) {
            if (isset($areaModels[$t['area']])) {
                RestaurantTable::updateOrCreate(
                    ['branch_id' => $mainBranch->id, 'table_number' => $t['num']],
                    [
                        'dining_area_id' => $areaModels[$t['area']]->id,
                        'capacity' => $t['cap'],
                        'status' => 'available',
                        'is_active' => true,
                    ]
                );
            }
        }

        // 3. Menu Categories
        $categories = [
            ['name' => 'Ethiopian Traditional', 'slug' => 'traditional', 'icon' => 'bi-fire', 'sort' => 1],
            ['name' => 'Grills & Meat Specialities', 'slug' => 'grills', 'icon' => 'bi-drumstick', 'sort' => 2],
            ['name' => 'Appetizers & Starters', 'slug' => 'appetizers', 'icon' => 'bi-egg-fried', 'sort' => 3],
            ['name' => 'Burgers & Sandwiches', 'slug' => 'burgers', 'icon' => 'bi-sandwich', 'sort' => 4],
            ['name' => 'Coffee & Hot Beverages', 'slug' => 'hot-drinks', 'icon' => 'bi-cup-hot', 'sort' => 5],
            ['name' => 'Fresh Juices & Spris', 'slug' => 'juices', 'icon' => 'bi-cup-straw', 'sort' => 6],
            ['name' => 'Beers & Refreshments', 'slug' => 'beers', 'icon' => 'bi-droplet', 'sort' => 7],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['slug']] = MenuCategory::updateOrCreate(
                ['branch_id' => $mainBranch->id, 'slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'sort_order' => $cat['sort'],
                    'is_active' => true,
                ]
            );
        }

        // 4. Menu Items
        $menuItems = [
            // Traditional (Kitchen)
            [
                'cat' => 'traditional',
                'code' => 'DOR-01',
                'name' => 'Doro Wat (Special Chicken Stew)',
                'desc' => 'Slow-cooked Ethiopian national dish with tender chicken drumstick, boiled egg, and rich berbere sauce.',
                'price' => 550.00,
                'cost' => 240.00,
                'dept' => 'kitchen',
                'modifiers' => ['Extra Injera' => 50.00, 'Extra Boiled Egg' => 40.00],
            ],
            [
                'cat' => 'traditional',
                'code' => 'KIT-01',
                'name' => 'Special Kitfo (Minced Lean Beef)',
                'desc' => 'Prime lean minced beef seasoned with niter kibbeh (clarified spiced butter) and mitmita, served with ayib and gomen.',
                'price' => 650.00,
                'cost' => 310.00,
                'dept' => 'kitchen',
                'modifiers' => ['Cooked Leb-Leb (Medium)' => 0.00, 'Cooked Well Done' => 0.00, 'Extra Ayib (Cottage Cheese)' => 60.00],
            ],
            [
                'cat' => 'traditional',
                'code' => 'BEY-01',
                'name' => 'Special Beyaynetu (Fasting Platter)',
                'desc' => 'A colorful platter of misir wat, shiro, alicha duba, gomen, keysir, and fosolia served over sourdough injera.',
                'price' => 380.00,
                'cost' => 140.00,
                'dept' => 'kitchen',
                'modifiers' => ['Extra Injera' => 50.00],
            ],

            // Grills (Kitchen)
            [
                'cat' => 'grills',
                'code' => 'SHK-01',
                'name' => 'Shekla Tibs (Sizzling Claypot Beef)',
                'desc' => 'Tender cubes of beef sautéed with onions, rosemary, and green chilies, served sizzling over hot clay coals.',
                'price' => 580.00,
                'cost' => 260.00,
                'dept' => 'kitchen',
                'modifiers' => ['Extra Spicy (Awaze)' => 20.00, 'Mild / No Pepper' => 0.00],
            ],

            // Appetizers (Kitchen)
            [
                'cat' => 'appetizers',
                'code' => 'SAM-01',
                'name' => 'Beef Sambusa (3 Pieces)',
                'desc' => 'Crispy golden pastry triangles stuffed with seasoned minced beef, scallions, and herbs.',
                'price' => 180.00,
                'cost' => 70.00,
                'dept' => 'kitchen',
            ],

            // Burgers (Kitchen)
            [
                'cat' => 'burgers',
                'code' => 'BUR-01',
                'name' => 'Kerefa Signature Beef Burger',
                'desc' => 'Grilled beef patty, melted cheddar, lettuce, caramelized onions, house special sauce in a toasted brioche bun.',
                'price' => 420.00,
                'cost' => 180.00,
                'dept' => 'kitchen',
                'modifiers' => ['Add Fried Egg' => 40.00, 'Extra Cheddar Cheese' => 60.00, 'Add French Fries' => 80.00],
            ],

            // Coffee & Hot Drinks (Bar)
            [
                'cat' => 'hot-drinks',
                'code' => 'CFE-01',
                'name' => 'Traditional Jebena Buna (Clay Pot Coffee)',
                'desc' => 'Freshly roasted organic Ethiopian highland coffee brewed in traditional clay pot and served in cini cups with popcorn.',
                'price' => 80.00,
                'cost' => 20.00,
                'dept' => 'bar',
                'modifiers' => ['With Rue (Tena Adam)' => 0.00, 'Sugar Free' => 0.00],
            ],
            [
                'cat' => 'hot-drinks',
                'code' => 'TEA-01',
                'name' => 'Spiced Special Tea',
                'desc' => 'Black tea infused with cardamom, cinnamon, cloves, and ginger.',
                'price' => 60.00,
                'cost' => 15.00,
                'dept' => 'bar',
            ],

            // Juices (Bar)
            [
                'cat' => 'juices',
                'code' => 'JUC-01',
                'name' => 'Avocado & Mango Spris',
                'desc' => 'Freshly pureed dense avocado layered with sweet mango juice and lime wedge.',
                'price' => 180.00,
                'cost' => 65.00,
                'dept' => 'bar',
                'modifiers' => ['Add Vimto Syrup' => 20.00, 'No Sugar Added' => 0.00],
            ],
            [
                'cat' => 'juices',
                'code' => 'JUC-02',
                'name' => 'Fresh Papaya & Pineapple Juice',
                'desc' => '100% natural tropical blend with ice.',
                'price' => 160.00,
                'cost' => 55.00,
                'dept' => 'bar',
            ],

            // Beers (Bar)
            [
                'cat' => 'beers',
                'code' => 'BER-01',
                'name' => 'St. George Beer (330ml Bottle)',
                'desc' => 'Classic Ethiopian lager brewed since 1922.',
                'price' => 90.00,
                'cost' => 55.00,
                'dept' => 'bar',
            ],
            [
                'cat' => 'beers',
                'code' => 'BER-02',
                'name' => 'Habesha Cold Beer (330ml)',
                'desc' => 'Golden lager with crisp balanced malt taste.',
                'price' => 95.00,
                'cost' => 58.00,
                'dept' => 'bar',
            ],
            [
                'cat' => 'beers',
                'code' => 'WAT-01',
                'name' => 'Ambo Mineral Water (500ml)',
                'desc' => 'Naturally sparkling mineral water from Ambo springs.',
                'price' => 50.00,
                'cost' => 25.00,
                'dept' => 'bar',
            ],
        ];

        foreach ($menuItems as $item) {
            if (isset($categoryModels[$item['cat']])) {
                $menuItem = MenuItem::updateOrCreate(
                    ['branch_id' => $mainBranch->id, 'code' => $item['code']],
                    [
                        'category_id' => $categoryModels[$item['cat']]->id,
                        'name' => $item['name'],
                        'description' => $item['desc'],
                        'price' => $item['price'],
                        'cost_price' => $item['cost'],
                        'preparation_department' => $item['dept'],
                        'is_available' => true,
                        'is_active' => true,
                    ]
                );

                if (! empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modName => $modPrice) {
                        MenuItemModifier::updateOrCreate(
                            ['menu_item_id' => $menuItem->id, 'name' => $modName],
                            [
                                'additional_price' => $modPrice,
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        }
    }
}
