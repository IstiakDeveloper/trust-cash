<?php

namespace Tests\Feature;

use App\Http\Middleware\InitializeTenancyFromSession;
use App\Models\BankTransaction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\CashReport;
use App\Services\FinancialReport;
use App\Services\InventoryPolicy;
use App\Services\InventoryReport;
use App\Services\LegacyInventoryCarryForward;
use App\Services\SaleCreator;
use App\Services\SaleReturnPosting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyInventoryCarryForwardTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $user;

    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 12:00:00');
        $this->withoutMiddleware(InitializeTenancyFromSession::class);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($this->user);
        $this->product = Product::create([
            'name' => 'Existing product', 'slug' => 'existing', 'sku' => 'EXISTING', 'type' => 'product',
            'category_id' => Category::factory()->create()->id, 'brand_id' => Brand::factory()->create()->id,
            'unit_id' => Unit::factory()->create()->id, 'cost_price' => 150, 'selling_price' => 250,
            'alert_quantity' => 2, 'status' => true,
        ]);
        $this->customerId = DB::table('customers')->insertGetId([
            'name' => 'Existing buyer', 'phone' => '01711111111', 'balance' => 1250, 'credit_limit' => 100000, 'status' => true,
            'created_at' => '2026-11-01', 'updated_at' => '2026-11-10',
        ]);
        $this->stock(10, 100, 'purchase', '2026-11-01', 10);
        $this->stock(10, 200, 'purchase', '2026-11-02', 20);
        $this->stock(-5, 200, 'sale', '2026-11-03', 15);
        $saleId = DB::table('sales')->insertGetId([
            'invoice_no' => 'OLD-001', 'customer_id' => $this->customerId, 'subtotal' => 1250, 'discount' => 0,
            'total' => 1250, 'paid' => 0, 'due' => 1250, 'payment_status' => 'due', 'business_date' => '2026-11-03',
            'created_by' => $this->user->id, 'created_at' => '2026-11-03', 'updated_at' => '2026-11-03',
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $saleId, 'product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 250, 'subtotal' => 1250,
            'created_at' => '2026-11-03', 'updated_at' => '2026-11-03',
        ]);
        DB::table('inventory_policies')->where('id', 1)->update(['method' => 'moving_weighted_average', 'accounting_ready' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function stock($quantity, $price, $type, $date, $available, ?int $variant = null): int
    {
        return DB::table('product_stocks')->insertGetId([
            'product_id' => $this->product->id, 'product_variant_id' => $variant,
            'quantity' => $quantity, 'total_quantity' => max(0, $quantity), 'unit_cost' => $price,
            'total_cost' => abs($quantity) * $price, 'available_quantity' => $available,
            'type' => $type, 'date' => $date, 'created_by' => $this->user->id, 'created_at' => $date, 'updated_at' => $date,
        ]);
    }

    public function test_existing_quantity_is_visible_and_carry_forward_preserves_all_original_records(): void
    {
        $before = DB::table('product_stocks')->orderBy('id')->get()->toJson();
        $sale = DB::table('sales')->first();
        $item = DB::table('sale_items')->first();
        $this->assertEquals(15, $this->product->availableQuantity());
        app(LegacyInventoryCarryForward::class)->ensure();
        $this->assertSame($before, DB::table('product_stocks')->where('type', '!=', 'legacy_opening')->orderBy('id')->get()->toJson());
        $this->assertEquals($sale, DB::table('sales')->first());
        $this->assertEquals($item, DB::table('sale_items')->first());
        $this->assertEquals(15, $this->product->availableQuantity());
        $this->assertDatabaseHas('inventory_balances', ['product_id' => $this->product->id, 'quantity' => 15, 'value' => 2250]);
        $this->assertDatabaseHas('product_stocks', ['type' => 'legacy_opening', 'quantity' => 0, 'lot_remaining_quantity' => 15]);
        $this->assertCount(1, $this->product->sellableLots());
        $count = DB::table('ledger_lines')->count();
        app(LegacyInventoryCarryForward::class)->ensure();
        $this->assertSame($count, DB::table('ledger_lines')->count());
        $this->assertDatabaseCount('legacy_inventory_openings', 1);
        $report = app(InventoryReport::class)->analysis('2026-11-01', '2026-11-11');
        $this->assertEquals(15, $report['totals']['available_quantity']);
        $this->assertEquals(2250, $report['totals']['available_stock_value']);
        $this->assertEquals(500, $report['totals']['total_profit']);
        $this->assertCount(1, $report['products']->first()['lots']);
    }

    public static function methods(): array
    {
        return ['FIFO' => ['fifo', 2750], 'Average' => ['moving_weighted_average', 3058.823529]];
    }

    #[DataProvider('methods')]
    public function test_selected_method_carries_old_average_forward_and_costs_only_new_activity(string $method, float $expectedCost): void
    {
        app(InventoryPolicy::class)->select($method);
        $this->assertEquals(2250, DB::table('inventory_balances')->value('value'));
        $this->assertEquals($method, DB::table('product_stocks')->where('type', 'legacy_opening')->value('costing_method'));
        $old = app(InventoryReport::class)->analysis('2026-11-01', '2026-11-10');
        ProductStock::create([
            'product_id' => $this->product->id, 'quantity' => 2, 'total_quantity' => 2, 'total_cost' => 1000,
            'unit_cost' => 500, 'type' => 'purchase', 'selling_price' => 600, 'date' => '2026-11-11', 'created_by' => $this->user->id,
        ]);
        $sale = app(SaleCreator::class)->create([
            ['product_id' => $this->product->id, 'quantity' => 16, 'unit_price' => 600],
        ], '9600', '0', '9600', '0', null, $this->customerId, null, $this->user->id, '2026-11-11', 'new-after-upgrade');
        $this->assertEqualsWithDelta($expectedCost, (float) $sale->items()->first()->cost_total, 0.000002);
        $this->assertEquals(1, $this->product->availableQuantity());
        $this->assertEquals($old, app(InventoryReport::class)->analysis('2026-11-01', '2026-11-10'));
        $this->assertTrue((bool) app(InventoryPolicy::class)->current()->locked);
    }

    public function test_old_reports_open_without_manually_reconstructing_or_removing_archived_history(): void
    {
        $id = $this->stock(99, 900, 'purchase', '2026-10-01', 99);
        DB::table('product_stocks')->where('id', $id)->update(['deleted_at' => '2026-10-02']);
        foreach (['/admin/products', '/admin/product-stocks', '/admin/reports/product-analysis', '/admin/reports/receipt-payment', '/admin/reports/income-expenditure', '/admin/reports/balance-sheet'] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->assertDatabaseHas('product_stocks', ['id' => $id, 'deleted_at' => '2026-10-02']);
        $this->assertEquals(15, $this->product->availableQuantity());
        $sheet = app(FinancialReport::class)->balanceSheet('2026-11-11');
        $this->assertEquals(2250, $sheet['property_and_assets']['stock_value']['period']);
        $this->assertEquals(1250, $sheet['property_and_assets']['customer_due']['period']);
        $this->assertEquals(500, $sheet['fund_and_liabilities']['net_profit']['period']);
        $this->assertEquals(0, app(CashReport::class)->balance('2026-11-11'));
    }

    public function test_preview_rolls_back_and_apply_is_repeatable_without_changing_legacy_history(): void
    {
        $before = DB::table('product_stocks')->get()->toJson();
        $this->artisan('inventory:carry-forward')->assertSuccessful();
        $this->assertDatabaseCount('inventory_balances', 0);
        $this->assertDatabaseCount('legacy_inventory_openings', 0);
        $this->assertSame($before, DB::table('product_stocks')->get()->toJson());
        $this->artisan('inventory:carry-forward', ['--apply' => true])->assertSuccessful();
        $count = DB::table('ledger_lines')->count();
        $this->artisan('inventory:carry-forward', ['--apply' => true])->assertSuccessful();
        $this->artisan('inventory:initialize', ['--apply' => true])->assertSuccessful();
        $this->assertSame($count, DB::table('ledger_lines')->count());
        $this->assertDatabaseCount('legacy_inventory_openings', 1);
    }

    public function test_saved_bank_and_customer_balances_survive_upgrade_and_later_cash_activity(): void
    {
        $bankId = DB::table('bank_accounts')->insertGetId([
            'account_name' => 'Existing cash', 'account_number' => 'OLD-CASH', 'bank_name' => 'Cash',
            'opening_balance' => 10000, 'current_balance' => 5000, 'status' => true,
            'created_at' => '2026-11-01', 'updated_at' => '2026-11-10',
        ]);
        DB::table('customers')->where('id', $this->customerId)->update(['balance' => 1500]);
        app(LegacyInventoryCarryForward::class)->ensure();
        $this->assertDatabaseHas('bank_accounts', ['id' => $bankId, 'current_balance' => 5000]);
        $this->assertDatabaseHas('customers', ['id' => $this->customerId, 'balance' => 1500]);
        $this->assertDatabaseHas('legacy_balance_carry_forwards', ['account' => 'cash:'.$bankId, 'difference' => -5000]);
        $sheet = app(FinancialReport::class)->balanceSheet('2026-11-11');
        $this->assertEquals(5000, $sheet['property_and_assets']['bank_balance']['period']);
        $this->assertEquals(1500, $sheet['property_and_assets']['customer_due']['period']);
        $this->assertEquals(0, $sheet['reconciliation']['cash_difference']);
        BankTransaction::create([
            'bank_account_id' => $bankId, 'transaction_type' => 'in', 'amount' => 1000,
            'date' => '2026-11-11', 'counter_account' => 'capital', 'category' => 'fund_in', 'created_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('bank_accounts', ['id' => $bankId, 'current_balance' => 6000]);
        $this->assertEquals(6000, app(CashReport::class)->balance('2026-11-11'));
        $this->assertEquals(10000, app(CashReport::class)->balance('2026-11-10'));
    }

    public function test_report_validation_renders_an_explanation_instead_of_silently_redirecting(): void
    {
        $this->get('/admin/reports/product-analysis?start_date=2026-11-12&end_date=2026-11-01')
            ->assertStatus(422)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Unavailable')->has('messages', 1));
    }

    public function test_returning_an_old_invoice_restores_the_preserved_average_without_rewriting_its_stock_rows(): void
    {
        $before = DB::table('product_stocks')->get()->keyBy('id');
        app(InventoryPolicy::class)->select('fifo');
        $line = DB::table('sale_items')->first();
        $return = app(SaleReturnPosting::class)->post([
            'sale_id' => $line->sale_id, 'customer_id' => $this->customerId,
            'return_date' => '2026-11-11', 'refund_status' => 'credited_to_due',
            'items' => [['sale_item_id' => $line->id, 'product_id' => $this->product->id, 'quantity' => 2]],
        ]);
        $this->assertEquals(500, $return->total_amount);
        $this->assertEquals(300, $return->items()->first()->cost_total);
        $this->assertEquals(17, $this->product->availableQuantity());
        $this->assertEquals(2550, DB::table('inventory_balances')->value('value'));
        foreach ($before as $id => $row) {
            $this->assertEquals($row, DB::table('product_stocks')->find($id));
        }
        $this->assertEquals(3, SaleItem::find($line->id)->returnable_quantity);
    }

    public function test_previous_returns_count_toward_the_original_invoice_entitlement_after_upgrade(): void
    {
        $line = DB::table('sale_items')->first();
        $returnId = DB::table('sale_returns')->insertGetId([
            'return_number' => 'OLD-RETURN', 'sale_id' => $line->sale_id, 'customer_id' => $this->customerId,
            'return_date' => '2026-11-04', 'total_amount' => 500, 'refund_amount' => 500,
            'refund_status' => 'credited_to_due', 'created_by' => $this->user->id,
            'created_at' => '2026-11-04', 'updated_at' => '2026-11-04',
        ]);
        $returnItemId = DB::table('sale_return_items')->insertGetId([
            'sale_return_id' => $returnId, 'product_id' => $this->product->id, 'quantity' => 2,
            'unit_price' => 250, 'subtotal' => 500, 'created_at' => '2026-11-04', 'updated_at' => '2026-11-04',
        ]);
        $this->stock(2, 250, 'sale_return', '2026-11-04', 17);
        DB::table('customers')->where('id', $this->customerId)->update(['balance' => 750]);
        DB::table('sales')->where('id', $line->sale_id)->update(['due' => 750]);
        app(LegacyInventoryCarryForward::class)->ensure();
        $this->assertEquals(3, SaleItem::find($line->id)->returnable_quantity);
        $return = app(SaleReturnPosting::class)->post([
            'sale_id' => $line->sale_id, 'customer_id' => $this->customerId, 'return_date' => '2026-11-11',
            'refund_status' => 'credited_to_due',
            'items' => [['sale_item_id' => $line->id, 'product_id' => $this->product->id, 'quantity' => 3]],
        ]);
        $this->assertEquals(750, $return->total_amount);
        $this->assertEquals(450, $return->items()->first()->cost_total);
        $this->assertEquals(0, SaleItem::find($line->id)->returnable_quantity);
        $this->assertEquals(20, $this->product->availableQuantity());
        $this->assertNull(DB::table('sale_return_items')->where('id', $returnItemId)->value('sale_item_id'));
    }

    public function test_fractional_variant_stock_has_its_own_opening_lot_and_value(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id, 'name' => 'Small pack', 'sku' => 'SMALL', 'status' => true,
        ]);
        $this->stock('2.5', '80', 'purchase', '2026-11-01', '2.5', $variant->id);
        app(InventoryPolicy::class)->select('fifo');
        $this->assertDatabaseHas('inventory_balances', ['product_id' => $this->product->id, 'variant_key' => $variant->id, 'quantity' => '2.5', 'value' => 200]);
        $this->assertDatabaseHas('inventory_balances', ['product_id' => $this->product->id, 'variant_key' => 0, 'quantity' => 15, 'value' => 2250]);
        $this->assertEquals(17.5, $this->product->availableQuantity());
        $this->assertCount(2, $this->product->sellableLots());
    }

    public function test_carry_forward_does_not_reuse_another_tenant_database_balance_or_ready_flag(): void
    {
        app(LegacyInventoryCarryForward::class)->ensure();
        $original = DB::getDefaultConnection();
        config(['database.connections.legacy_isolation' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        try {
            DB::setDefaultConnection('legacy_isolation');
            Artisan::call('migrate', ['--database' => 'legacy_isolation', '--force' => true]);
            foreach (['roles', 'users', 'categories', 'brands', 'units', 'products'] as $table) {
                foreach (DB::connection($original)->table($table)->get() as $row) {
                    DB::table($table)->insert((array) $row);
                }
            }
            $this->stock(4, 50, 'purchase', '2026-11-01', 4);
            DB::table('inventory_policies')->where('id', 1)->update(['accounting_ready' => false, 'method' => 'fifo']);
            app(LegacyInventoryCarryForward::class)->ensure();
            $this->assertEquals(4, DB::table('inventory_balances')->value('quantity'));
            $this->assertEquals(200, DB::table('inventory_balances')->value('value'));
            $this->assertDatabaseCount('legacy_inventory_openings', 1);
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('legacy_isolation');
        }
        $this->assertEquals(15, DB::table('inventory_balances')->value('quantity'));
        $this->assertEquals(2250, DB::table('inventory_balances')->value('value'));
        $this->assertDatabaseCount('legacy_inventory_openings', 1);
    }

    public function test_interrupted_carry_forward_rolls_back_every_generated_row_and_retains_originals(): void
    {
        $before = DB::table('product_stocks')->get()->toJson();
        DB::listen(function ($query) {
            if (str_contains(strtolower($query->sql), 'insert into') && str_contains($query->sql, 'legacy_inventory_openings')) {
                throw new \RuntimeException('Simulated interruption before carry-forward completion');
            }
        });
        try {
            app(LegacyInventoryCarryForward::class)->ensure();
            $this->fail('The simulated interruption must abort carry-forward.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Simulated interruption', $exception->getMessage());
        }
        $this->assertSame($before, DB::table('product_stocks')->get()->toJson());
        $this->assertDatabaseCount('legacy_inventory_openings', 0);
        $this->assertDatabaseCount('legacy_inventory_values', 0);
        $this->assertDatabaseCount('inventory_balances', 0);
        $this->assertDatabaseCount('ledger_lines', 0);
        $this->assertFalse((bool) DB::table('inventory_policies')->value('accounting_ready'));
    }

    public function test_legacy_invoice_costs_match_product_analysis_even_when_an_old_stock_movement_is_missing(): void
    {
        DB::table('product_stocks')->where('type', 'sale')->delete();
        $analysis = app(InventoryReport::class)->analysis('2026-11-01', '2026-11-11');
        $income = app(FinancialReport::class)->income('2026-11-01', '2026-11-11');
        $this->assertEquals($analysis['totals']['total_profit'], $income['sales_profit']['period']);
        $this->assertEquals(20, $analysis['totals']['available_quantity']);
        $this->assertEquals(3000, $analysis['totals']['available_stock_value']);
        $this->assertEquals(750, $income['cost_of_sales']['period']);
    }

    public function test_preserved_invoice_cannot_be_deleted_without_its_controlled_return(): void
    {
        app(LegacyInventoryCarryForward::class)->ensure();
        $line = SaleItem::first();
        try {
            $line->delete();
            $this->fail('Original carried-forward invoice lines must be retained.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('linked return or credit', $exception->getMessage());
        }
        $this->assertDatabaseHas('sale_items', ['id' => $line->id]);
        $this->assertEquals(15, $this->product->availableQuantity());
    }
}
