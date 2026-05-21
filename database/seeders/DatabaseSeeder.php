<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\HeadOfAccounts;
use App\Models\SubHeadOfAccounts;
use App\Models\ChartOfAccounts;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\MeasurementUnit;
use App\Models\ProductCategory;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BarcodeSequence;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now    = now();
        $userId = 1;

        // ─────────────────────────────────────────────────────────────
        // 🔑  Super Admin User
        // ─────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@gmail.com',
                'password' => Hash::make('12345678'),
            ]
        );

        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin->assignRole($superAdmin);

        // ─────────────────────────────────────────────────────────────
        // 📌  Module Permissions  (index · create · edit · delete · print)
        // ─────────────────────────────────────────────────────────────
        $modules = [
            // User Management
            'user_roles',
            'users',

            // Accounts
            'coa',
            'shoa',
            'vendors',   // ← add this

            // Products
            'products',
            'product_categories',
            'attributes',

            // Purchase
            'purchase_orders',   // NEW – purchase order before invoice
            'purchase_invoices',
            'purchase_return',

            // Vouchers
            'vouchers',

            // Production
            'production',
            'production_receiving',
            'production_return',
        ];

        $actions = ['index', 'create', 'edit', 'delete', 'print'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$module.$action"]);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 📊  Report Permissions  (view-only, no CRUD)
        // ─────────────────────────────────────────────────────────────
        $reports = ['inventory', 'purchase', 'production', 'accounts'];

        foreach ($reports as $report) {
            Permission::firstOrCreate(['name' => "reports.$report"]);
        }

        // Assign all permissions to Superadmin
        $superAdmin->syncPermissions(Permission::all());

        // ─────────────────────────────────────────────────────────────
        // 📚  Heads of Accounts
        // ─────────────────────────────────────────────────────────────
        HeadOfAccounts::insert([
            ['id' => 1, 'name' => 'Assets',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Liabilities', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Equity',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Revenue',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Expenses',    'created_at' => $now, 'updated_at' => $now],
        ]);

        // ─────────────────────────────────────────────────────────────
        // 📑  Sub Heads of Accounts
        // ─────────────────────────────────────────────────────────────
        SubHeadOfAccounts::insert([
            // Assets
            ['id' => 1,  'hoa_id' => 1, 'name' => 'Cash & Cash Equivalents', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'hoa_id' => 1, 'name' => 'Bank Accounts',           'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'hoa_id' => 1, 'name' => 'Accounts Receivable',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'hoa_id' => 1, 'name' => 'Inventory',               'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'hoa_id' => 1, 'name' => 'Other Current Assets',    'created_at' => $now, 'updated_at' => $now],

            // Liabilities
            ['id' => 6,  'hoa_id' => 2, 'name' => 'Accounts Payable',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'hoa_id' => 2, 'name' => 'Loans & Borrowings',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'hoa_id' => 2, 'name' => 'Other Liabilities',       'created_at' => $now, 'updated_at' => $now],

            // Equity
            ['id' => 9,  'hoa_id' => 3, 'name' => 'Owner Capital',           'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'hoa_id' => 3, 'name' => 'Retained Earnings',       'created_at' => $now, 'updated_at' => $now],

            // Revenue
            ['id' => 11, 'hoa_id' => 4, 'name' => 'Sales Revenue',           'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'hoa_id' => 4, 'name' => 'Other Income',            'created_at' => $now, 'updated_at' => $now],

            // Expenses
            ['id' => 13, 'hoa_id' => 5, 'name' => 'Cost of Goods Sold',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'hoa_id' => 5, 'name' => 'Operating Expenses',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'hoa_id' => 5, 'name' => 'Salaries & Wages',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'hoa_id' => 5, 'name' => 'Production Expenses',     'created_at' => $now, 'updated_at' => $now],
        ]);

        // ─────────────────────────────────────────────────────────────
        // 🗂  Chart of Accounts
        // ─────────────────────────────────────────────────────────────
        $coaData = [
            // ── ASSETS ──────────────────────────────────────────────
            // Cash
            ['account_code' => '101001', 'shoa_id' => 1,  'name' => 'Shop Cash',              'account_type' => 'cash',      'receivables' => 0, 'payables' => 0],
            ['account_code' => '101002', 'shoa_id' => 1,  'name' => 'Petty Cash',             'account_type' => 'cash',      'receivables' => 0, 'payables' => 0],
            // Bank
            ['account_code' => '102001', 'shoa_id' => 2,  'name' => 'Meezan Bank',            'account_type' => 'bank',      'receivables' => 0, 'payables' => 0],
            ['account_code' => '102002', 'shoa_id' => 2,  'name' => 'HBL Account',            'account_type' => 'bank',      'receivables' => 0, 'payables' => 0],
            // Accounts Receivable
            ['account_code' => '103001', 'shoa_id' => 3,  'name' => 'Customer 01',            'account_type' => 'customer',  'receivables' => 0, 'payables' => 0],
            // Inventory
            ['account_code' => '104001', 'shoa_id' => 4,  'name' => 'Stock in Hand',          'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],
            ['account_code' => '104002', 'shoa_id' => 4,  'name' => 'Raw Material Stock',     'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],
            ['account_code' => '104003', 'shoa_id' => 4,  'name' => 'Work In Progress',       'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],
            ['account_code' => '104004', 'shoa_id' => 4,  'name' => 'Finished Goods Stock',   'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],
            // Other Current Assets
            ['account_code' => '105001', 'shoa_id' => 5,  'name' => 'Advance to Suppliers',   'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],
            ['account_code' => '105002', 'shoa_id' => 5,  'name' => 'Prepaid Expenses',       'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],
            ['account_code' => '105003', 'shoa_id' => 5,  'name' => 'Security Deposits',      'account_type' => 'asset',     'receivables' => 0, 'payables' => 0],

            // ── LIABILITIES ─────────────────────────────────────────
            ['account_code' => '205001', 'shoa_id' => 6,  'name' => 'Vendor 01',              'account_type' => 'vendor',    'receivables' => 0, 'payables' => 0],
            ['account_code' => '206001', 'shoa_id' => 7,  'name' => 'Bank Loan',              'account_type' => 'liability', 'receivables' => 0, 'payables' => 0],
            ['account_code' => '207001', 'shoa_id' => 8,  'name' => 'Salaries Payable',       'account_type' => 'liability', 'receivables' => 0, 'payables' => 0],
            ['account_code' => '207002', 'shoa_id' => 8,  'name' => 'Tax Payable',            'account_type' => 'liability', 'receivables' => 0, 'payables' => 0],
            ['account_code' => '207003', 'shoa_id' => 8,  'name' => 'Advance from Customers', 'account_type' => 'liability', 'receivables' => 0, 'payables' => 0],

            // ── EQUITY ──────────────────────────────────────────────
            ['account_code' => '301001', 'shoa_id' => 9,  'name' => 'Owners Equity',          'account_type' => 'equity',    'receivables' => 0, 'payables' => 0],
            ['account_code' => '302001', 'shoa_id' => 10, 'name' => 'Retained Earnings',      'account_type' => 'equity',    'receivables' => 0, 'payables' => 0],

            // ── REVENUE ─────────────────────────────────────────────
            ['account_code' => '401001', 'shoa_id' => 11, 'name' => 'Sales Revenue',          'account_type' => 'revenue',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '401002', 'shoa_id' => 11, 'name' => 'Sales Return',           'account_type' => 'revenue',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '401003', 'shoa_id' => 11, 'name' => 'Sales Discount',         'account_type' => 'revenue',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '402001', 'shoa_id' => 12, 'name' => 'Purchase Discount',      'account_type' => 'revenue',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '402002', 'shoa_id' => 12, 'name' => 'Other Income',           'account_type' => 'revenue',   'receivables' => 0, 'payables' => 0],

            // ── EXPENSES ────────────────────────────────────────────
            // COGS
            ['account_code' => '501001', 'shoa_id' => 13, 'name' => 'Cost of Goods Sold',     'account_type' => 'cogs',      'receivables' => 0, 'payables' => 0],
            ['account_code' => '501002', 'shoa_id' => 13, 'name' => 'Purchase Return',        'account_type' => 'cogs',      'receivables' => 0, 'payables' => 0],
            ['account_code' => '501003', 'shoa_id' => 13, 'name' => 'Inventory Loss',         'account_type' => 'cogs',      'receivables' => 0, 'payables' => 0], // NEW – stock adjustment loss
            // Operating Expenses
            ['account_code' => '502001', 'shoa_id' => 14, 'name' => 'Conveyance Expense',     'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '502002', 'shoa_id' => 14, 'name' => 'Labour Expense',         'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '502003', 'shoa_id' => 14, 'name' => 'Rent Expense',           'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '502004', 'shoa_id' => 14, 'name' => 'Utilities Expense',      'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '502005', 'shoa_id' => 14, 'name' => 'Repair & Maintenance',   'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '502006', 'shoa_id' => 14, 'name' => 'Miscellaneous Expense',  'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            // Salaries
            ['account_code' => '503001', 'shoa_id' => 15, 'name' => 'Salaries Expense',       'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            // Production Expenses
            ['account_code' => '504001', 'shoa_id' => 16, 'name' => 'Production Labour',      'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '504002', 'shoa_id' => 16, 'name' => 'Production Overhead',    'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
            ['account_code' => '504003', 'shoa_id' => 16, 'name' => 'Raw Material Consumed',  'account_type' => 'expense',   'receivables' => 0, 'payables' => 0],
        ];

        foreach ($coaData as $data) {
            ChartOfAccounts::create(array_merge($data, [
                'opening_date' => now()->toDateString(),
                'credit_limit' => 0.00,
                'remarks'      => null,
                'address'      => null,
                'contact_no'   => null,
                'created_by'   => $userId,
                'updated_by'   => $userId,
            ]));
        }

        // ─────────────────────────────────────────────────────────────
        // 🏷  Attributes (Size, Colors)
        // ─────────────────────────────────────────────────────────────
        Attribute::insert([
            ['id' => 1, 'name' => 'Size',   'slug' => 'size',   'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Colors', 'slug' => 'colors', 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        AttributeValue::insert([
            // SIZE values (attribute_id = 1)
            ['id' => 1,  'attribute_id' => 1, 'value' => '52',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'attribute_id' => 1, 'value' => '54',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'attribute_id' => 1, 'value' => '56',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'attribute_id' => 1, 'value' => '58',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'attribute_id' => 1, 'value' => '60',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'attribute_id' => 1, 'value' => 'Free Size', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'attribute_id' => 1, 'value' => 'Small',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'attribute_id' => 1, 'value' => 'Medium',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'attribute_id' => 1, 'value' => 'Large',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'attribute_id' => 1, 'value' => 'X-Large',   'created_at' => $now, 'updated_at' => $now],

            // COLOR values (attribute_id = 2)
            ['id' => 11, 'attribute_id' => 2, 'value' => 'Black',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'attribute_id' => 2, 'value' => 'Blue',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'attribute_id' => 2, 'value' => 'Yellow',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'attribute_id' => 2, 'value' => 'Green',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'attribute_id' => 2, 'value' => 'Orange',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'attribute_id' => 2, 'value' => 'Purple',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'attribute_id' => 2, 'value' => 'Red-Orange',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'attribute_id' => 2, 'value' => 'Yellow-Orange', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'attribute_id' => 2, 'value' => 'Yellow-Green',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'attribute_id' => 2, 'value' => 'Blue-Green',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 21, 'attribute_id' => 2, 'value' => 'Blue-Purple',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 22, 'attribute_id' => 2, 'value' => 'Red-Purple',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 23, 'attribute_id' => 2, 'value' => 'Crimson',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 24, 'attribute_id' => 2, 'value' => 'Maroon',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 25, 'attribute_id' => 2, 'value' => 'Scarlet',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 26, 'attribute_id' => 2, 'value' => 'Burgundy',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 27, 'attribute_id' => 2, 'value' => 'Navy Blue',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 28, 'attribute_id' => 2, 'value' => 'Sky Blue',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 29, 'attribute_id' => 2, 'value' => 'Cobalt Blue',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 30, 'attribute_id' => 2, 'value' => 'Teal',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 31, 'attribute_id' => 2, 'value' => 'Olive Green',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 32, 'attribute_id' => 2, 'value' => 'Lime Green',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 33, 'attribute_id' => 2, 'value' => 'Forest Green',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 34, 'attribute_id' => 2, 'value' => 'Emerald Green', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 35, 'attribute_id' => 2, 'value' => 'Mustard Yellow','created_at' => $now, 'updated_at' => $now],
            ['id' => 36, 'attribute_id' => 2, 'value' => 'Gold',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 37, 'attribute_id' => 2, 'value' => 'Lemon Yellow',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 38, 'attribute_id' => 2, 'value' => 'Lavender',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 39, 'attribute_id' => 2, 'value' => 'Violet',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 40, 'attribute_id' => 2, 'value' => 'Plum',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 41, 'attribute_id' => 2, 'value' => 'Magenta',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 42, 'attribute_id' => 2, 'value' => 'Peach',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 43, 'attribute_id' => 2, 'value' => 'Coral',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 44, 'attribute_id' => 2, 'value' => 'Amber',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 45, 'attribute_id' => 2, 'value' => 'Baby Pink',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 46, 'attribute_id' => 2, 'value' => 'Hot Pink',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 47, 'attribute_id' => 2, 'value' => 'Salmon',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 48, 'attribute_id' => 2, 'value' => 'Rose',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 49, 'attribute_id' => 2, 'value' => 'White',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 50, 'attribute_id' => 2, 'value' => 'Gray',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 51, 'attribute_id' => 2, 'value' => 'Beige',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 52, 'attribute_id' => 2, 'value' => 'Brown',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 53, 'attribute_id' => 2, 'value' => 'Ivory',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 54, 'attribute_id' => 2, 'value' => 'Silver',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 55, 'attribute_id' => 2, 'value' => 'Bronze',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 56, 'attribute_id' => 2, 'value' => 'Copper',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 57, 'attribute_id' => 2, 'value' => 'Pastel Blue',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 58, 'attribute_id' => 2, 'value' => 'Pastel Pink',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 59, 'attribute_id' => 2, 'value' => 'Pastel Green',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 60, 'attribute_id' => 2, 'value' => 'Pastel Yellow', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 61, 'attribute_id' => 2, 'value' => 'Neon Green',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 62, 'attribute_id' => 2, 'value' => 'Neon Pink',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 63, 'attribute_id' => 2, 'value' => 'Neon Orange',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 64, 'attribute_id' => 2, 'value' => 'Neon Blue',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 65, 'attribute_id' => 2, 'value' => 'Offwhite',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 66, 'attribute_id' => 2, 'value' => 'Cream',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 67, 'attribute_id' => 2, 'value' => 'Fawn',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 68, 'attribute_id' => 2, 'value' => 'Teal Blue',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 69, 'attribute_id' => 2, 'value' => 'Light Green',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 70, 'attribute_id' => 2, 'value' => 'Malaysian Grey','created_at' => $now, 'updated_at' => $now],
            ['id' => 71, 'attribute_id' => 2, 'value' => 'Skin',          'created_at' => $now, 'updated_at' => $now],
            ['id' => 72, 'attribute_id' => 2, 'value' => 'Light Grey',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 73, 'attribute_id' => 2, 'value' => 'Dark Grey',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 74, 'attribute_id' => 2, 'value' => 'Mehendi',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 75, 'attribute_id' => 2, 'value' => 'Camel',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 76, 'attribute_id' => 2, 'value' => 'Pista',         'created_at' => $now, 'updated_at' => $now],
            ['id' => 77, 'attribute_id' => 2, 'value' => 'Light Purple',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 78, 'attribute_id' => 2, 'value' => 'Light Pink',    'created_at' => $now, 'updated_at' => $now],
        ]);

        // ─────────────────────────────────────────────────────────────
        // 📦  Product Categories
        // ─────────────────────────────────────────────────────────────
        ProductCategory::insert([
            ['id' => 1,  'name' => 'Abaya Fabric',           'code' => 'ABBY-FAB',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'name' => 'Abaya',                  'code' => 'ABBY',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'name' => 'Abaya Hijab',            'code' => 'ABBY-HIJ',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'name' => "Kid's Abaya",            'code' => 'K-ABBY',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'name' => 'Scarf',                  'code' => 'SCF',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'name' => 'Ladies FG',              'code' => 'L-FG',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'name' => "Men's Fancy Fabric",     'code' => 'M-FAB-F',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'name' => "Men's Plain Fabric",     'code' => 'M-FAB-P',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'name' => 'Kids FG',                'code' => 'K-FG',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'name' => 'Accessories',            'code' => 'ACS',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'name' => 'Kameez Shalwar Plain',   'code' => 'KAS-P',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'name' => 'Kameez Shalwar Design',  'code' => 'KAS-D',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'name' => 'Kameez Shalwar C.E',     'code' => 'KAS-CE',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'name' => 'Kameez Shalwar H.W',     'code' => 'KAS-HW',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'name' => 'Kurta Shalwar Plain',    'code' => 'KUS-P',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'name' => 'Kurta Shalwar Design',   'code' => 'KUS-D',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'name' => 'Kurta Shalwar C.E',      'code' => 'KUS-CE',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'name' => 'Kurta Shalwar H.W',      'code' => 'KUS-HW',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'name' => 'Kurta Chicken',          'code' => 'K-C',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'name' => 'Kurta Fancy',            'code' => 'K-F',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 21, 'name' => 'Kurta C.E',              'code' => 'K-CE',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 22, 'name' => 'Kurta H.W',              'code' => 'K-HW',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 23, 'name' => 'Kurta Pajama Plain',     'code' => 'KPJ-P',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 24, 'name' => 'Kurta Pajama Fancy',     'code' => 'KPJ-F',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 25, 'name' => '3PC Suit Plain',         'code' => '3PC-P',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 26, 'name' => '3PC Suit Fancy',         'code' => '3PC-F',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 27, 'name' => 'WC Plain',               'code' => 'WC-P',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 28, 'name' => 'WC Messuri',             'code' => 'WC-M',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 29, 'name' => 'WC Jamawar',             'code' => 'WC-JW',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 30, 'name' => 'WC Raw Silk',            'code' => 'WC-RS',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 31, 'name' => 'WC Jute',                'code' => 'WC-J',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 32, 'name' => 'WC Suiting',             'code' => 'WC-S',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 33, 'name' => 'PC Plain',               'code' => 'PC-P',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 34, 'name' => 'PC Messuri',             'code' => 'PC-M',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 35, 'name' => 'PC Jamawar',             'code' => 'PC-JW',     'created_at' => $now, 'updated_at' => $now],
            ['id' => 36, 'name' => 'Pajama Plain',           'code' => 'PJ-P',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 37, 'name' => 'Pajama Pocket',          'code' => 'PJ-PKT',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 38, 'name' => 'Shalwar Plain',          'code' => 'SHALWAR-P', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 39, 'name' => 'Sherwani Plain',         'code' => 'SHER-P',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 40, 'name' => 'Sherwani Embroidery',    'code' => 'SHER-E',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 41, 'name' => 'Coat Casual',            'code' => 'CC',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 42, 'name' => 'Coat Formal',            'code' => 'CF',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 43, 'name' => 'Shawl',                  'code' => 'SHAWL',     'created_at' => $now, 'updated_at' => $now],
        ]);

        // ─────────────────────────────────────────────────────────────
        // 📏  Measurement Units
        // ─────────────────────────────────────────────────────────────
        MeasurementUnit::insert([
            ['id' => 1, 'name' => 'Piece',       'shortcode' => 'pcs'],
            ['id' => 2, 'name' => 'Meter',       'shortcode' => 'm'],
            ['id' => 3, 'name' => 'Square Feet', 'shortcode' => 'sq.ft'],
            ['id' => 4, 'name' => 'Yards',       'shortcode' => 'yrds'],
        ]);

        // ─────────────────────────────────────────────────────────────
        // 🔢  Barcode Sequences
        // ─────────────────────────────────────────────────────────────
        $sequences = [
            ['prefix' => 'GLOBAL', 'next_number' => 1],
            ['prefix' => 'FG',     'next_number' => 1],
            ['prefix' => 'RAW',    'next_number' => 1],
            ['prefix' => 'SRV',    'next_number' => 1],
            ['prefix' => 'PRD',    'next_number' => 1],
            ['prefix' => 'VAR',    'next_number' => 1],
        ];

        foreach ($sequences as $seq) {
            BarcodeSequence::firstOrCreate(
                ['prefix' => $seq['prefix']],
                ['next_number' => $seq['next_number']]
            );
        }
    }
}