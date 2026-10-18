<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExtraIncome;
use App\Models\ExtraIncomeCategory;
use App\Models\Fund;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Unit;
use App\Models\User;
use App\Services\SaleCreator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantDemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        if (Setting::get('demo_data_seeded') === '1') {
            $this->command?->warn('Demo data already exists. Use --force to seed again after clearing the shop.');

            return;
        }

        DB::transaction(function () {
            $this->call(TenantDatabaseSeeder::class);

            $admin = $this->ensureAdmin();
            $this->seedSettings();
            $branch = $this->seedBranch();
            [$cash, $bank] = $this->seedAccounts();
            $this->seedFunds($admin, $cash, $bank);
            $units = $this->units();
            $categories = $this->seedCategories();
            $brands = $this->seedBrands();
            $products = $this->seedProducts($categories, $brands, $units);
            $suppliers = $this->seedSuppliers();
            $this->seedPurchases($admin, $cash, $suppliers, $products);
            $customers = $this->seedCustomers($branch);
            $this->seedSales($admin, $cash, $bank, $products, $customers);
            $this->seedExpenses($admin, $cash);
            $this->seedExtraIncome($admin, $bank);

            Setting::set('demo_data_seeded', '1');
        });
    }

    public function ensureAdmin(): User
    {
        $tenantId = tenant('id') ?: 'demo';
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'permissions' => null]
        );

        $user = User::query()->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Demo Shop Owner',
                'username' => $tenantId,
                'email' => $tenantId.'@demo.trustcash.local',
                'phone' => '01700000001',
                'password' => self::DEMO_PASSWORD,
                'role_id' => $adminRole->id,
                'status' => 1,
            ]);
        } else {
            $user->forceFill([
                'username' => $user->username ?: $tenantId,
                'password' => self::DEMO_PASSWORD,
                'role_id' => $user->role_id ?: $adminRole->id,
                'status' => 1,
            ])->save();
        }

        return $user->fresh();
    }

    private function seedSettings(): void
    {
        Setting::set('business_name', 'Demo Super Shop');
        Setting::set('business_phone', '01700000001');
        Setting::set('business_email', 'demo@trustcash.local');
        Setting::set('business_address', 'Dhanmondi, Dhaka');
    }

    private function seedBranch(): Branch
    {
        return Branch::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'phone' => '01700000001',
                'address' => 'Dhanmondi, Dhaka',
                'is_main' => true,
                'status' => 'active',
            ]
        );
    }

    /**
     * @return array{0: BankAccount, 1: BankAccount}
     */
    private function seedAccounts(): array
    {
        $cash = BankAccount::firstOrCreate(
            ['account_number' => 'CASH-001'],
            [
                'account_name' => 'Shop Cash',
                'bank_name' => 'Cash in Hand',
                'branch_name' => 'Counter',
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => true,
            ]
        );

        $bank = BankAccount::firstOrCreate(
            ['account_number' => 'DBBL-001'],
            [
                'account_name' => 'Business Current',
                'bank_name' => 'Dutch-Bangla Bank',
                'branch_name' => 'Dhanmondi',
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => true,
            ]
        );

        return [$cash, $bank];
    }

    private function seedFunds(User $admin, BankAccount $cash, BankAccount $bank): void
    {
        $this->addFund($admin, $cash, 150000, 'Owner capital', 'Opening cash for shop');
        $this->addFund($admin, $bank, 80000, 'Owner capital', 'Opening bank deposit');
    }

    private function addFund(User $admin, BankAccount $account, float $amount, string $from, string $note): void
    {
        Fund::create([
            'bank_account_id' => $account->id,
            'type' => 'in',
            'amount' => $amount,
            'from_who' => $from,
            'description' => $note,
            'date' => now()->subDays(10)->toDateString(),
            'created_by' => $admin->id,
        ]);

        BankTransaction::create([
            'bank_account_id' => $account->id,
            'transaction_type' => 'in',
            'amount' => $amount,
            'description' => "Fund in: {$from}",
            'date' => now()->subDays(10)->toDateString(),
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @return array<string, Unit>
     */
    private function units(): array
    {
        return [
            'kg' => Unit::where('short_name', 'kg')->firstOrFail(),
            'ltr' => Unit::where('short_name', 'ltr')->firstOrFail(),
            'pcs' => Unit::where('short_name', 'pcs')->firstOrFail(),
            'pkt' => Unit::where('short_name', 'pkt')->firstOrFail(),
        ];
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $items = [
            'grocery' => 'Grocery',
            'oil' => 'Oil & Ghee',
            'personal' => 'Personal Care',
        ];

        $out = [];
        foreach ($items as $key => $name) {
            $out[$key] = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $name, 'status' => true]
            );
        }

        return $out;
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(): array
    {
        $items = [
            'fresh' => 'Fresh',
            'pran' => 'Pran',
        ];

        $out = [];
        foreach ($items as $key => $name) {
            $out[$key] = Brand::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => true]
            );
        }

        return $out;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Brand>  $brands
     * @param  array<string, Unit>  $units
     * @return array<int, Product>
     */
    private function seedProducts(array $categories, array $brands, array $units): array
    {
        $catalog = [
            ['Rice 25kg', 'grocery', 'fresh', 'kg', 1450, 1650],
            ['Soybean Oil 2L', 'oil', 'fresh', 'ltr', 310, 360],
            ['Sugar 1kg', 'grocery', 'fresh', 'kg', 118, 135],
            ['Onion 1kg', 'grocery', 'fresh', 'kg', 55, 70],
            ['Chanachur 150g', 'grocery', 'pran', 'pkt', 38, 50],
            ['Soap 100g', 'personal', 'pran', 'pcs', 32, 45],
        ];

        $products = [];
        foreach ($catalog as $index => [$name, $cat, $brand, $unit, $cost, $sell]) {
            $products[] = Product::firstOrCreate(
                ['sku' => 'DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'slug' => Str::slug($name).'-demo',
                    'barcode' => '89000000000'.($index + 1),
                    'category_id' => $categories[$cat]->id,
                    'brand_id' => $brands[$brand]->id,
                    'unit_id' => $units[$unit]->id,
                    'cost_price' => $cost,
                    'selling_price' => $sell,
                    'alert_quantity' => 2,
                    'description' => 'Demo product',
                    'status' => true,
                ]
            );
        }

        return $products;
    }

    /**
     * @return array<int, Supplier>
     */
    private function seedSuppliers(): array
    {
        return [
            Supplier::firstOrCreate(
                ['phone' => '01800000011'],
                [
                    'name' => 'Karim Traders',
                    'company_name' => 'Karim Wholesale',
                    'email' => 'karim@supplier.test',
                    'address' => 'Kawran Bazar, Dhaka',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'status' => 'active',
                ]
            ),
            Supplier::firstOrCreate(
                ['phone' => '01800000012'],
                [
                    'name' => 'Nila Enterprise',
                    'company_name' => 'Nila Supply',
                    'email' => 'nila@supplier.test',
                    'address' => 'Jatrabari, Dhaka',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'status' => 'active',
                ]
            ),
        ];
    }

    /**
     * @param  array<int, Supplier>  $suppliers
     * @param  array<int, Product>  $products
     */
    private function seedPurchases(User $admin, BankAccount $cash, array $suppliers, array $products): void
    {
        $firstItems = [];
        foreach ($products as $product) {
            $firstItems[] = [
                'product' => $product,
                'qty' => 8,
                'price' => (float) $product->cost_price,
            ];
        }

        $this->createPurchase($admin, $suppliers[0], $cash, now()->subDays(8)->toDateString(), $firstItems, paidFully: true);

        $this->createPurchase(
            $admin,
            $suppliers[1],
            $cash,
            now()->subDays(5)->toDateString(),
            [
                ['product' => $products[0], 'qty' => 4, 'price' => (float) $products[0]->cost_price],
                ['product' => $products[1], 'qty' => 6, 'price' => (float) $products[1]->cost_price],
            ],
            paidFully: false
        );
    }

    /**
     * @param  array<int, array{product: Product, qty: float, price: float}>  $items
     */
    private function createPurchase(
        User $admin,
        Supplier $supplier,
        BankAccount $cash,
        string $date,
        array $items,
        bool $paidFully
    ): void {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }

        $paid = $paidFully ? $subtotal : round($subtotal * 0.4, 2);
        $due = round($subtotal - $paid, 2);
        $seq = Purchase::count() + 1;
        $number = 'PUR-DEMO-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        $purchase = Purchase::create([
            'purchase_number' => $number,
            'supplier_id' => $supplier->id,
            'bank_account_id' => $paid > 0 ? $cash->id : null,
            'purchase_date' => $date,
            'subtotal' => $subtotal,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => $subtotal,
            'paid_amount' => $paid,
            'due_amount' => $due,
            'payment_status' => $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
            'purchase_status' => 'received',
            'note' => 'Demo purchase',
            'created_by' => $admin->id,
        ]);

        foreach ($items as $item) {
            $product = $item['product'];
            $line = $item['qty'] * $item['price'];

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'unit_id' => $product->unit_id,
                'purchase_price' => $item['price'],
                'selling_price' => $product->selling_price,
                'quantity' => $item['qty'],
                'subtotal' => $line,
            ]);

            $before = (float) (ProductStock::where('product_id', $product->id)->orderByDesc('id')->value('available_quantity') ?? 0);
            $after = $before + $item['qty'];

            $stockData = [
                'product_id' => $product->id,
                'quantity' => $item['qty'],
                'total_quantity' => $item['qty'],
                'available_quantity' => $after,
                'unit_cost' => $item['price'],
                'total_cost' => $line,
                'type' => 'purchase',
                'date' => $date,
                'note' => "Purchase #{$number}",
                'created_by' => $admin->id,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('product_stocks', 'bank_account_id')) {
                $stockData['bank_account_id'] = $cash->id;
            }
            ProductStock::create($stockData);

            StockMovement::create([
                'product_id' => $product->id,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'quantity' => $item['qty'],
                'before_quantity' => $before,
                'after_quantity' => $after,
                'type' => 'purchase',
                'created_by' => $admin->id,
            ]);
        }

        if ($paid > 0) {
            SupplierPayment::create([
                'supplier_id' => $supplier->id,
                'purchase_id' => $purchase->id,
                'bank_account_id' => $cash->id,
                'amount' => $paid,
                'payment_method' => 'cash',
                'payment_date' => $date,
                'reference_no' => $number,
                'note' => "Payment for #{$number}",
                'created_by' => $admin->id,
            ]);

            BankTransaction::create([
                'bank_account_id' => $cash->id,
                'transaction_type' => 'out',
                'amount' => $paid,
                'description' => "Purchase Payment: #{$number}",
                'date' => $date,
                'created_by' => $admin->id,
            ]);
        }

        if ($due > 0) {
            $supplier->increment('current_balance', $due);
        }
    }

    /**
     * @return array<int, Customer>
     */
    private function seedCustomers(Branch $branch): array
    {
        $rows = [
            ['Rahim Mia', '01711000001', 20000],
            ['Fatema Begum', '01711000002', 25000],
            ['Jamal Uddin', '01711000003', 30000],
            ['Walk-in Regular', '01711000004', 5000],
        ];

        $customers = [];
        foreach ($rows as [$name, $phone, $limit]) {
            $customers[] = Customer::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => $name,
                    'email' => Str::slug($name).'@customer.test',
                    'address' => 'Dhaka',
                    'branch_code' => $branch->code,
                    'branch_name' => $branch->name,
                    'credit_limit' => $limit,
                    'balance' => 0,
                    'points' => 0,
                    'status' => true,
                ]
            );
        }

        return $customers;
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<int, Customer>  $customers
     */
    private function seedSales(User $admin, BankAccount $cash, BankAccount $bank, array $products, array $customers): void
    {
        $creator = app(SaleCreator::class);

        $this->posSale($creator, $admin, $cash, $products, null, [
            [$products[4], 2],
            [$products[5], 1],
        ], paidRatio: 1);

        $this->posSale($creator, $admin, $bank, $products, $customers[0], [
            [$products[2], 3],
            [$products[3], 2],
        ], paidRatio: 1);

        $this->posSale($creator, $admin, $cash, $products, $customers[1], [
            [$products[1], 1],
            [$products[4], 2],
        ], paidRatio: 0.4);

        $this->posSale($creator, $admin, $cash, $products, $customers[2], [
            [$products[0], 1],
            [$products[5], 2],
        ], paidRatio: 0);
    }

    /**
     * @param  array<int, array{0: Product, 1: float}>  $lines
     */
    private function posSale(
        SaleCreator $creator,
        User $admin,
        BankAccount $account,
        array $products,
        ?Customer $customer,
        array $lines,
        float $paidRatio
    ): void {
        $items = [];
        $subtotal = 0;
        foreach ($lines as [$product, $qty]) {
            $price = (float) $product->selling_price;
            $items[] = [
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $price,
            ];
            $subtotal += $qty * $price;
        }

        $paid = round($subtotal * $paidRatio, 2);
        $creator->create(
            $items,
            $subtotal,
            0,
            $subtotal,
            $paid,
            $paid > 0 ? $account->id : null,
            $customer?->id,
            'Demo sale',
            $admin->id,
            now()->subDays(2)->toDateString()
        );
    }

    private function seedExpenses(User $admin, BankAccount $cash): void
    {
        $rent = ExpenseCategory::firstOrCreate(
            ['name' => 'Shop Rent'],
            ['description' => 'Monthly rent', 'status' => true]
        );
        $utility = ExpenseCategory::firstOrCreate(
            ['name' => 'Utility'],
            ['description' => 'Electricity / gas', 'status' => true]
        );

        foreach ([
            [$rent, 8000, 'October shop rent'],
            [$utility, 1200, 'Electricity bill'],
        ] as [$category, $amount, $note]) {
            Expense::create([
                'expense_category_id' => $category->id,
                'bank_account_id' => $cash->id,
                'amount' => $amount,
                'description' => $note,
                'reference_no' => 'EXP-DEMO-'.$category->id,
                'date' => now()->subDays(3)->toDateString(),
                'created_by' => $admin->id,
            ]);

            BankTransaction::create([
                'bank_account_id' => $cash->id,
                'transaction_type' => 'out',
                'amount' => $amount,
                'description' => "Expense: {$note}",
                'date' => now()->subDays(3)->toDateString(),
                'created_by' => $admin->id,
            ]);
        }
    }

    private function seedExtraIncome(User $admin, BankAccount $bank): void
    {
        $category = ExtraIncomeCategory::firstOrCreate(
            ['name' => 'Commission'],
            ['description' => 'Supplier commission', 'status' => true, 'created_by' => $admin->id]
        );

        ExtraIncome::create([
            'bank_account_id' => $bank->id,
            'category_id' => $category->id,
            'amount' => 1500,
            'title' => 'Supplier commission',
            'description' => 'Demo extra income',
            'date' => now()->subDay()->toDateString(),
            'created_by' => $admin->id,
        ]);

        BankTransaction::create([
            'bank_account_id' => $bank->id,
            'transaction_type' => 'in',
            'amount' => 1500,
            'description' => 'Extra Income (Commission): Supplier commission',
            'date' => now()->subDay()->toDateString(),
            'created_by' => $admin->id,
        ]);
    }
}
