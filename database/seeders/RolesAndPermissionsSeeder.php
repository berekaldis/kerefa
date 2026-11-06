<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Core & Administration
            ['module' => 'users', 'slug' => 'users.view', 'name' => 'View Users', 'description' => 'View list of system users'],
            ['module' => 'users', 'slug' => 'users.create', 'name' => 'Create Users', 'description' => 'Create new system users'],
            ['module' => 'users', 'slug' => 'users.edit', 'name' => 'Edit Users', 'description' => 'Modify existing user details and statuses'],
            ['module' => 'users', 'slug' => 'users.delete', 'name' => 'Delete Users', 'description' => 'Remove or deactivate system users'],

            ['module' => 'roles', 'slug' => 'roles.view', 'name' => 'View Roles', 'description' => 'View roles and their assigned permissions'],
            ['module' => 'roles', 'slug' => 'roles.manage', 'name' => 'Manage Roles', 'description' => 'Create and modify roles and permission mappings'],

            ['module' => 'branches', 'slug' => 'branches.view', 'name' => 'View Branches', 'description' => 'View branch details'],
            ['module' => 'branches', 'slug' => 'branches.manage', 'name' => 'Manage Branches', 'description' => 'Create and configure branches'],
            ['module' => 'branches', 'slug' => 'branches.switch', 'name' => 'Switch Branch Context', 'description' => 'Switch active operational branch context'],

            ['module' => 'settings', 'slug' => 'settings.view', 'name' => 'View Settings', 'description' => 'View system configurations'],
            ['module' => 'settings', 'slug' => 'settings.manage', 'name' => 'Manage Settings', 'description' => 'Modify system settings'],
            ['module' => 'settings', 'slug' => 'audit.view', 'name' => 'View Audit Logs', 'description' => 'Inspect comprehensive system audit trail'],

            // RMS / POS
            ['module' => 'pos', 'slug' => 'pos.view', 'name' => 'Access POS', 'description' => 'Access the POS terminal and tables'],
            ['module' => 'pos', 'slug' => 'pos.orders.create', 'name' => 'Create Orders', 'description' => 'Take new customer dine-in, takeaway, and delivery orders'],
            ['module' => 'pos', 'slug' => 'pos.orders.edit', 'name' => 'Edit Orders', 'description' => 'Modify existing open orders'],
            ['module' => 'pos', 'slug' => 'pos.orders.cancel', 'name' => 'Cancel Orders', 'description' => 'Cancel orders or items with authorization'],
            ['module' => 'pos', 'slug' => 'pos.tables.manage', 'name' => 'Manage Tables', 'description' => 'Configure dining areas and tables'],
            ['module' => 'pos', 'slug' => 'pos.menu.manage', 'name' => 'Manage Menu', 'description' => 'Manage menu items, prices, and categories'],
            ['module' => 'pos', 'slug' => 'pos.kds.view', 'name' => 'Kitchen Display', 'description' => 'Access Kitchen Display System (KDS) tickets'],
            ['module' => 'pos', 'slug' => 'pos.bds.view', 'name' => 'Bar Display', 'description' => 'Access Bar Display System (BDS) tickets'],

            // Stock & Inventory
            ['module' => 'inventory', 'slug' => 'inventory.view', 'name' => 'View Inventory', 'description' => 'View stock items, balances, and locations'],
            ['module' => 'inventory', 'slug' => 'inventory.manage', 'name' => 'Manage Inventory Items', 'description' => 'Create and modify stock items and units'],
            ['module' => 'inventory', 'slug' => 'inventory.transfer', 'name' => 'Stock Transfers', 'description' => 'Initiate and receive internal stock transfers'],
            ['module' => 'inventory', 'slug' => 'inventory.adjust', 'name' => 'Stock Adjustments', 'description' => 'Record stock adjustments and wastage'],
            ['module' => 'inventory', 'slug' => 'inventory.count', 'name' => 'Physical Counts', 'description' => 'Perform physical stock counts and reconciliations'],

            // Procurement
            ['module' => 'procurement', 'slug' => 'procurement.view', 'name' => 'View Procurement', 'description' => 'View purchase orders and vendors'],
            ['module' => 'procurement', 'slug' => 'procurement.requests', 'name' => 'Purchase Requisitions', 'description' => 'Create and submit purchase requests'],
            ['module' => 'procurement', 'slug' => 'procurement.orders', 'name' => 'Purchase Orders', 'description' => 'Generate and approve purchase orders'],
            ['module' => 'procurement', 'slug' => 'procurement.receiving', 'name' => 'Goods Receiving', 'description' => 'Process Goods Received Notes (GRN)'],

            // F&B
            ['module' => 'fb', 'slug' => 'fb.recipes.view', 'name' => 'View Recipes', 'description' => 'View recipes and ingredient specifications'],
            ['module' => 'fb', 'slug' => 'fb.recipes.manage', 'name' => 'Manage Recipes', 'description' => 'Create and modify recipes and portions'],
            ['module' => 'fb', 'slug' => 'fb.production', 'name' => 'Kitchen Production', 'description' => 'Record production batches and yields'],
            ['module' => 'fb', 'slug' => 'fb.costing', 'name' => 'Costing Analysis', 'description' => 'Analyze food cost percentages and menu profitability'],

            // Finance
            ['module' => 'finance', 'slug' => 'finance.view', 'name' => 'View Financials', 'description' => 'View financial overview and accounts'],
            ['module' => 'finance', 'slug' => 'finance.journals', 'name' => 'Post Journals', 'description' => 'Create and post balanced journal entries'],
            ['module' => 'finance', 'slug' => 'finance.ledger', 'name' => 'General Ledger', 'description' => 'Inspect general ledger and trial balance'],
            ['module' => 'finance', 'slug' => 'finance.reports', 'name' => 'Financial Reports', 'description' => 'Generate P&L, balance sheets, and cash flow'],
            ['module' => 'finance', 'slug' => 'finance.expenses', 'name' => 'Manage Expenses', 'description' => 'Record and approve operational expenses'],

            // HR
            ['module' => 'hr', 'slug' => 'hr.employees.view', 'name' => 'View Employees', 'description' => 'View employee directory and contracts'],
            ['module' => 'hr', 'slug' => 'hr.employees.manage', 'name' => 'Manage Employees', 'description' => 'Register and manage staff profiles'],
            ['module' => 'hr', 'slug' => 'hr.attendance', 'name' => 'Manual Attendance', 'description' => 'Record and verify daily shift attendance'],
            ['module' => 'hr', 'slug' => 'hr.shifts', 'name' => 'Manage Shifts', 'description' => 'Create and schedule work shifts'],
            ['module' => 'hr', 'slug' => 'hr.payroll', 'name' => 'Process Payroll', 'description' => 'Calculate and approve payroll runs and payslips'],

            // Reports
            ['module' => 'reports', 'slug' => 'reports.view', 'name' => 'View Reports', 'description' => 'Access management and operational reports'],
            ['module' => 'reports', 'slug' => 'reports.export', 'name' => 'Export Reports', 'description' => 'Export reports to PDF or Excel'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['slug']] = Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
        }

        // Roles definition
        $roles = [
            'super-admin' => [
                'name' => 'Super Administrator',
                'description' => 'Full unrestricted access to all ERP modules and branches',
                'is_system' => true,
                'permissions' => array_keys($permissionModels),
            ],
            'general-manager' => [
                'name' => 'General Manager',
                'description' => 'Executive management oversight, consolidated reports, approvals',
                'is_system' => true,
                'permissions' => [
                    'users.view', 'branches.view', 'branches.switch', 'settings.view', 'audit.view',
                    'pos.view', 'pos.orders.create', 'pos.orders.edit', 'pos.orders.cancel', 'pos.tables.manage', 'pos.menu.manage',
                    'inventory.view', 'inventory.manage', 'inventory.transfer', 'inventory.adjust', 'inventory.count',
                    'procurement.view', 'procurement.orders',
                    'fb.recipes.view', 'fb.costing',
                    'finance.view', 'finance.reports', 'finance.expenses',
                    'hr.employees.view', 'hr.attendance', 'hr.payroll',
                    'reports.view', 'reports.export',
                ],
            ],
            'branch-manager' => [
                'name' => 'Branch Manager',
                'description' => 'Complete operational control within the assigned branch',
                'is_system' => true,
                'permissions' => [
                    'users.view', 'pos.view', 'pos.orders.create', 'pos.orders.edit', 'pos.orders.cancel', 'pos.tables.manage', 'pos.menu.manage',
                    'pos.kds.view', 'pos.bds.view',
                    'inventory.view', 'inventory.transfer', 'inventory.adjust', 'inventory.count',
                    'procurement.view', 'procurement.requests', 'procurement.receiving',
                    'fb.recipes.view', 'fb.production',
                    'finance.view', 'finance.expenses',
                    'hr.employees.view', 'hr.attendance', 'hr.shifts',
                    'reports.view',
                ],
            ],
            'cashier' => [
                'name' => 'Cashier',
                'description' => 'POS orders management and shift reconciliation',
                'is_system' => true,
                'permissions' => [
                    'pos.view', 'pos.orders.create', 'pos.orders.edit', 'reports.view',
                ],
            ],
            'waiter' => [
                'name' => 'Waiter',
                'description' => 'Table orders taking, dining area management, service updates',
                'is_system' => true,
                'permissions' => [
                    'pos.view', 'pos.orders.create', 'pos.orders.edit',
                ],
            ],
            'kitchen-staff' => [
                'name' => 'Kitchen Staff',
                'description' => 'Kitchen Display System (KDS), preparation status updates',
                'is_system' => true,
                'permissions' => [
                    'pos.kds.view', 'fb.recipes.view',
                ],
            ],
            'bar-staff' => [
                'name' => 'Bar Staff',
                'description' => 'Bar Display System (BDS), beverage preparation status updates',
                'is_system' => true,
                'permissions' => [
                    'pos.bds.view', 'fb.recipes.view',
                ],
            ],
            'storekeeper' => [
                'name' => 'Storekeeper',
                'description' => 'Stock inventory receipts, issues, transfers, and physical counts',
                'is_system' => true,
                'permissions' => [
                    'inventory.view', 'inventory.manage', 'inventory.transfer', 'inventory.adjust', 'inventory.count',
                    'procurement.view', 'procurement.receiving',
                    'reports.view',
                ],
            ],
            'procurement-officer' => [
                'name' => 'Procurement Officer',
                'description' => 'Supplier management, requisitions, and purchase orders',
                'is_system' => true,
                'permissions' => [
                    'procurement.view', 'procurement.requests', 'procurement.orders', 'procurement.receiving',
                    'inventory.view', 'reports.view',
                ],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'description' => 'Double-entry accounting, general ledger, journals, financial reports',
                'is_system' => true,
                'permissions' => [
                    'finance.view', 'finance.journals', 'finance.ledger', 'finance.reports', 'finance.expenses',
                    'procurement.view', 'reports.view', 'reports.export',
                ],
            ],
            'hr-officer' => [
                'name' => 'HR Officer',
                'description' => 'Staff records, manual shift attendance, leave, and payroll processing',
                'is_system' => true,
                'permissions' => [
                    'hr.employees.view', 'hr.employees.manage', 'hr.attendance', 'hr.shifts', 'hr.payroll',
                    'reports.view', 'reports.export',
                ],
            ],
            'fb-controller' => [
                'name' => 'F&B Controller',
                'description' => 'Recipe costing, food cost % calculation, portion and wastage control',
                'is_system' => true,
                'permissions' => [
                    'fb.recipes.view', 'fb.recipes.manage', 'fb.production', 'fb.costing',
                    'inventory.view', 'pos.view', 'reports.view', 'reports.export',
                ],
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_system' => $data['is_system'] ?? false,
                ]
            );

            $assignedPermissionIds = [];
            foreach ($data['permissions'] as $permSlug) {
                if (isset($permissionModels[$permSlug])) {
                    $assignedPermissionIds[] = $permissionModels[$permSlug]->id;
                }
            }

            $role->permissions()->sync($assignedPermissionIds);
        }
    }
}
