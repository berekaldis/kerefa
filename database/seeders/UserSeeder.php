<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'MAIN')->first();
        $adminDept = Department::where('code', 'MGT')->first();
        $kitchenDept = Department::where('code', 'KIT')->first();
        $barDept = Department::where('code', 'BAR')->first();
        $diningDept = Department::where('code', 'DIN')->first();
        $storeDept = Department::where('code', 'STR')->first();
        $accDept = Department::where('code', 'ACC')->first();

        // 1. Super Administrator
        $admin = User::updateOrCreate(
            ['email' => 'admin@kerefa.com'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'phone' => '+251 91 100 0001',
                'password' => Hash::make('Admin1234!'),
                'branch_id' => $mainBranch?->id,
                'department_id' => $adminDept?->id,
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
        if ($mainBranch) {
            $admin->accessibleBranches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // 2. Sample operational staff for initial testing and demonstration
        $staffUsers = [
            [
                'name' => 'General Manager',
                'username' => 'gm',
                'email' => 'gm@kerefa.com',
                'role' => 'general-manager',
                'dept' => $adminDept?->id,
            ],
            [
                'name' => 'Main Cashier',
                'username' => 'cashier',
                'email' => 'cashier@kerefa.com',
                'role' => 'cashier',
                'dept' => $diningDept?->id,
            ],
            [
                'name' => 'Kdst Grma',
                'username' => 'kdst',
                'email' => 'kdst@kerefa.com',
                'role' => 'waiter',
                'dept' => $diningDept?->id,
            ],
            [
                'name' => 'Senior Waiter',
                'username' => 'waiter',
                'email' => 'waiter@kerefa.com',
                'role' => 'waiter',
                'dept' => $diningDept?->id,
            ],
            [
                'name' => 'Selamawit Tadesse',
                'username' => 'selam',
                'email' => 'selam@kerefa.com',
                'role' => 'waiter',
                'dept' => $diningDept?->id,
            ],
            [
                'name' => 'Almaz Ayana',
                'username' => 'almaz',
                'email' => 'almaz@kerefa.com',
                'role' => 'waiter',
                'dept' => $diningDept?->id,
            ],
            [
                'name' => 'Head Chef',
                'username' => 'chef',
                'email' => 'kitchen@kerefa.com',
                'role' => 'kitchen-staff',
                'dept' => $kitchenDept?->id,
            ],
            [
                'name' => 'Head Bartender',
                'username' => 'bartender',
                'email' => 'bar@kerefa.com',
                'role' => 'bar-staff',
                'dept' => $barDept?->id,
            ],
            [
                'name' => 'Store Manager',
                'username' => 'storekeeper',
                'email' => 'store@kerefa.com',
                'role' => 'storekeeper',
                'dept' => $storeDept?->id,
            ],
            [
                'name' => 'Senior Accountant',
                'username' => 'accountant',
                'email' => 'accountant@kerefa.com',
                'role' => 'accountant',
                'dept' => $accDept?->id,
            ],
        ];

        foreach ($staffUsers as $staff) {
            $user = User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'username' => $staff['username'],
                    'phone' => '+251 91 100 0002',
                    'password' => Hash::make('Password123!'),
                    'branch_id' => $mainBranch?->id,
                    'department_id' => $staff['dept'],
                    'is_active' => true,
                ]
            );

            $role = Role::where('slug', $staff['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
            if ($mainBranch) {
                $user->accessibleBranches()->sync([$mainBranch->id]);
            }
        }
    }
}
