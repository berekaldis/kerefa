<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Seeder;

class BranchAndDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Kerefa Restaurant - Main Branch',
                'address' => 'Bole Sub-City, Addis Ababa, Ethiopia',
                'phone' => '+251 11 600 0000',
                'email' => 'info@kerefa.com',
                'is_main' => true,
                'is_active' => true,
                'currency' => 'ETB',
                'settings' => [
                    'vat_rate' => 15.00,
                    'service_charge_rate' => 10.00,
                    'bill_generation_enabled' => false, // STRICTLY DISABLED IN INITIAL RELEASE
                ],
            ]
        );

        $departments = [
            ['code' => 'MGT', 'name' => 'General Management', 'type' => 'admin'],
            ['code' => 'KIT', 'name' => 'Kitchen Department', 'type' => 'kitchen'],
            ['code' => 'BAR', 'name' => 'Beverage & Bar', 'type' => 'bar'],
            ['code' => 'DIN', 'name' => 'Dining & Floor Service', 'type' => 'dining'],
            ['code' => 'STR', 'name' => 'Main Store & Inventory', 'type' => 'inventory'],
            ['code' => 'ACC', 'name' => 'Finance & Accounting', 'type' => 'finance'],
            ['code' => 'HRD', 'name' => 'Human Resources', 'type' => 'hr'],
            ['code' => 'PRC', 'name' => 'Procurement & Logistics', 'type' => 'general'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['branch_id' => $mainBranch->id, 'code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'type' => $dept['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
