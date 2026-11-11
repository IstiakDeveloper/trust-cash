<?php

namespace Tests\Feature;

use App\Exports\BankTransactionReportExport;
use App\Exports\ReceiptPaymentExport;
use App\Exports\SaleReportExport;
use App\Http\Controllers\Admin\BalanceSheetController;
use App\Http\Controllers\Admin\BankReportController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BankTransactionReportController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerSalesReportController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\IncomeExpenditureController;
use App\Http\Controllers\Admin\ProductAnalysisReportController;
use App\Http\Controllers\Admin\ProductStockReportController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\ReceiptPaymentController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SaleReportController;
use App\Http\Middleware\ApplyBusinessTimezone;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\BankReporting;
use App\Services\CashReport;
use App\Services\FinancialLedger;
use App\Services\FinancialReport;
use App\Services\InventoryPolicy;
use App\Services\SaleCreator;
use App\Services\SaleReturnPosting;
use App\Services\SalesReporting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryAccountingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private BankAccount $bank;

    private Customer $customer;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 12:00:00');
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($this->user);
        $this->product = Product::create([
            'name' => 'Test product', 'slug' => 'test-product', 'sku' => 'TEST', 'type' => 'product',
            'category_id' => Category::factory()->create()->id, 'brand_id' => Brand::factory()->create()->id,
            'unit_id' => Unit::factory()->create()->id, 'selling_price' => 180, 'cost_price' => 100, 'status' => true,
        ]);
        $this->bank = BankAccount::create([
            'account_name' => 'Cash', 'account_number' => 'CASH', 'bank_name' => 'Cash',
            'opening_balance' => 10000, 'current_balance' => 10000, 'opened_on' => '2026-10-01', 'status' => true,
        ]);
        $this->customer = Customer::create(['name' => 'Buyer', 'phone' => '01700000000', 'credit_limit' => 100000, 'balance' => 0, 'status' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function methods(): array
    {
        return ['FIFO' => ['fifo', 1240, 960], 'Moving average' => ['moving_weighted_average', 1320, 880]];
    }

    private function receive(string $quantity, string $cost, string $date = '2026-11-01', string $sellingPrice = '180'): ProductStock
    {
        return DB::transaction(function () use ($quantity, $cost, $date, $sellingPrice) {
            $total = bcmul($quantity, $cost, 6);
            $stock = ProductStock::create([
                'product_id' => $this->product->id, 'quantity' => $quantity, 'total_cost' => $total,
                'unit_cost' => $cost, 'type' => 'purchase', 'date' => $date, 'selling_price' => $sellingPrice,
                'created_by' => $this->user->id, 'bank_account_id' => $this->bank->id,
            ]);
            BankTransaction::create([
                'bank_account_id' => $this->bank->id, 'transaction_type' => 'out', 'amount' => $total,
                'date' => $date, 'category' => 'stock_purchase', 'created_by' => $this->user->id,
            ]);

            return $stock;
        });
    }

    private function sell(string $quantity = '12', string $price = '180', string $date = '2026-11-03', float $paid = 0)
    {
        $total = (float) bcmul($quantity, $price, 2);

        return app(SaleCreator::class)->create([
            ['product_id' => $this->product->id, 'quantity' => $quantity, 'unit_price' => $price],
        ], $total, 0, $total, $paid, $this->bank->id, $this->customer->id, null, $this->user->id, $date);
    }

    #[DataProvider('methods')]
    public function test_both_methods_snapshot_cost_and_reconcile_all_reports(string $method, int $cost, int $closing): void
    {
        app(InventoryPolicy::class)->select($method);
        $this->receive('10', '100', sellingPrice: '150');
        $this->receive('10', '120', '2026-11-02');
        $sale = $this->sell(paid: 1000);
        $this->assertEquals($cost, $sale->items()->first()->cost_total);
        $analysis = Product::getProductAnalysis('2026-11-01', '2026-11-03');
        $this->assertEquals($cost, $analysis['totals']['sales_cost']);
        $this->assertEquals($closing, $analysis['totals']['available_stock_value']);
        $this->assertEquals(8, $analysis['totals']['available_quantity']);
        $income = app(FinancialReport::class)->income('2026-11-01', '2026-11-03');
        $this->assertEquals(2160 - $cost, $income['sales_profit']['period']);
        $sheet = app(FinancialReport::class)->balanceSheet('2026-11-03');
        $this->assertEquals(1160, $sheet['property_and_assets']['customer_due']['period']);
        foreach ($sheet['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
        $before = $analysis;
        $this->receive('5', '200', '2026-11-04');
        $this->assertEquals($cost, $sale->items()->first()->cost_total);
        $this->assertEquals($before['totals'], Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals']);
    }

    #[DataProvider('methods')]
    public function test_return_restores_original_cost_and_prevents_repeated_overreturn(string $method, int $cost, int $closing): void
    {
        app(InventoryPolicy::class)->select($method);
        $this->receive('10', '100');
        $this->receive('10', '120', '2026-11-02');
        $sale = $this->sell();
        $line = $sale->items()->first();
        $return = app(SaleReturnPosting::class)->post([
            'sale_id' => $sale->id, 'customer_id' => $this->customer->id, 'return_date' => '2026-11-04',
            'refund_status' => 'credited_to_due', 'items' => [
                ['sale_item_id' => $line->id, 'product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);
        // FIFO returns reverse the first two allocated units, not the current purchase price.
        $expectedCost = $method === 'fifo' ? 200 : 220;
        $this->assertEquals($expectedCost, $return->items()->first()->cost_total);
        $this->assertEquals($closing + $expectedCost, Product::getProductAnalysis('2026-11-01', '2026-11-04')['totals']['available_stock_value']);
        foreach (app(FinancialReport::class)->balanceSheet('2026-11-04')['reconciliation'] as $difference) {
            $this->assertEquals(0, $difference);
        }
        $this->expectException(ValidationException::class);
        app(SaleReturnPosting::class)->post([
            'sale_id' => $sale->id, 'customer_id' => $this->customer->id, 'return_date' => '2026-11-05', 'refund_status' => 'credited_to_due',
            'items' => [['sale_item_id' => $line->id, 'product_id' => $this->product->id, 'quantity' => 11]],
        ]);
    }

    public function test_method_is_locked_after_first_posting(): void
    {
        $this->receive('10', '100');
        $this->expectException(ValidationException::class);
        app(InventoryPolicy::class)->select('moving_weighted_average');
    }

    public function test_insufficient_stock_rolls_back_entire_invoice_and_ledger(): void
    {
        $this->receive('2', '100');
        $count = DB::table('ledger_lines')->count();
        try {
            $this->sell('3');
            $this->fail('Expected stock rejection.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals($count, DB::table('ledger_lines')->count());
        $this->assertEquals(2, DB::table('inventory_balances')->value('quantity'));
    }

    public function test_fractional_quantities_are_preserved(): void
    {
        $this->receive('2.5', '100');
        $sale = $this->sell('0.75');
        $this->assertEquals('0.750000', $sale->items()->first()->quantity);
        $this->assertEquals(75, $sale->items()->first()->cost_total);
        $this->assertEquals('1.750000', Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals']['available_quantity']);
    }

    public function test_backdated_posting_is_rejected_without_mutating_lots(): void
    {
        $stock = $this->receive('10', '100');
        $this->sell('1');
        $before = $stock->fresh()->lot_remaining_quantity;
        try {
            $this->receive('1', '90', '2026-11-02');
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertStringContainsString('precedes', $e->getMessage());
        }
        $this->assertEquals($before, $stock->fresh()->lot_remaining_quantity);
    }

    public function test_cash_report_includes_inactive_accounts_refunds_and_unknown_transactions(): void
    {
        $this->bank->update(['status' => false]);
        foreach ([['in', 100, 'sale_collection'], ['out', 30, 'customer_refund'], ['out', 5, 'other']] as [$direction, $amount, $category]) {
            BankTransaction::create(['bank_account_id' => $this->bank->id, 'transaction_type' => $direction,
                'amount' => $amount, 'category' => $category, 'date' => '2026-11-02', 'created_by' => $this->user->id]);
        }
        $cash = app(CashReport::class)->build('2026-11-01', '2026-11-03');
        $this->assertEquals(10065, $cash['payment']['closing_cash_at_bank']['period']);
        $this->assertCount(2, $cash['payment']['rows']);
        $this->assertEquals(0, $cash['reconciliation_difference']);
        $export = new ReceiptPaymentExport($cash['receipt'], $cash['payment']);
        $this->assertEquals(10065, collect($export->array())->first(fn ($row) => $row[1] === 'Closing cash and bank')[2]);
    }

    public function test_journal_retry_is_idempotent_and_rejects_unbalanced_documents(): void
    {
        $ledger = app(FinancialLedger::class);
        $ledger->pair('test', 1, 'cash:1', 'capital', 100, '2026-11-01');
        $count = DB::table('ledger_lines')->count();
        $ledger->pair('test', 1, 'cash:1', 'capital', 100, '2026-11-01');
        $this->assertEquals($count, DB::table('ledger_lines')->count());
        $this->expectException(\LogicException::class);
        $ledger->sync('test', 2, [['account' => 'cash:1', 'amount' => 1]], '2026-11-01');
    }

    public function test_policy_cutover_preserves_carrying_value_and_historical_reports(): void
    {
        $this->receive('10', '100');
        $this->receive('10', '120', '2026-11-02');
        $this->sell();
        $before = Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals'];
        $this->artisan('inventory:change-method', ['method' => 'moving_weighted_average', '--date' => '2026-11-10', '--reason' => 'Reviewed cutover', '--apply' => true])->assertSuccessful();
        $this->assertEquals('moving_weighted_average', app(InventoryPolicy::class)->current()->method);
        $this->assertEquals($before, Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals']);
        $this->assertEquals(960, DB::table('inventory_balances')->value('value'));
    }

    public function test_purchase_controller_allocates_invoice_costs_and_partial_supplier_payment(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier', 'opening_balance' => 0, 'current_balance' => 0]);
        $request = Request::create('/', 'POST', [
            'supplier_id' => $supplier->id, 'purchase_date' => '2026-11-01', 'bank_account_id' => $this->bank->id,
            'discount_type' => 'fixed', 'discount_amount' => 10, 'tax_amount' => 5, 'shipping_cost' => 30, 'paid_amount' => 50,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'purchase_price' => 10, 'selling_price' => 15],
                ['product_id' => $this->product->id, 'quantity' => 10, 'purchase_price' => 20, 'selling_price' => 30],
            ],
        ]);
        app(PurchaseController::class)->store($request);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('product_stocks', 2);
        $this->assertEquals(325, ProductStock::sum('total_cost'));
        $this->assertEquals(275, $supplier->fresh()->current_balance);
        $this->assertEquals(9950, $this->bank->fresh()->current_balance);
        $this->assertEquals(180, $this->product->fresh()->selling_price);
        $this->assertEquals([15, 30], ProductStock::orderBy('id')->pluck('selling_price')->map(fn ($price) => (float) $price)->all());
        foreach (app(FinancialReport::class)->balanceSheet('2026-11-01')['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
    }

    public function test_fifo_screens_price_the_oldest_lot_and_list_what_remains(): void
    {
        $this->receive('10', '100', sellingPrice: '150');
        $this->receive('4', '120', '2026-11-02', '220');
        $pos = app(PosController::class)->products()->firstWhere('id', $this->product->id);
        $this->assertEquals(150, (float) $pos['selling_price']);
        $this->assertCount(2, $pos['lots']);
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $page = json_decode(app(ProductStockController::class)->index($request)->toResponse($request)->getContent(), true);
        $lots = $page['props']['stocks']['data'][0]['lots'];
        $this->assertCount(2, $lots);
        $this->assertEquals(10, (float) $lots[0]['remaining_quantity']);
        $this->assertEquals(150, (float) $lots[0]['selling_price']);
        $this->assertEquals(220, (float) $lots[1]['selling_price']);
    }

    public function test_average_pos_uses_the_product_price_and_sends_no_lots(): void
    {
        app(InventoryPolicy::class)->select('moving_weighted_average');
        $this->receive('10', '100', sellingPrice: '150');
        $pos = app(PosController::class)->products()->firstWhere('id', $this->product->id);
        $this->assertEquals(180, (float) $pos['selling_price']);
        $this->assertCount(0, $pos['lots']);
    }

    public function test_fifo_receipts_continue_lot_numbers_and_reuse_the_previous_selling_price(): void
    {
        $this->receive('5', '100', sellingPrice: '150');
        $this->assertSame('lot-1', ProductStock::value('lot_number'));
        $supplier = Supplier::create(['name' => 'Lot supplier', 'opening_balance' => 0, 'current_balance' => 0]);
        app(PurchaseController::class)->store(Request::create('/', 'POST', [
            'supplier_id' => $supplier->id, 'purchase_date' => '2026-11-02', 'bank_account_id' => $this->bank->id, 'paid_amount' => 80,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 4, 'purchase_price' => 20],
            ],
        ]));
        $lots = ProductStock::orderBy('id')->get();
        $this->assertSame(['lot-1', 'lot-2'], $lots->pluck('lot_number')->all());
        $this->assertEquals(150, (float) $lots->last()->selling_price);
        $this->assertEquals(180, (float) $this->product->fresh()->selling_price);
    }

    public function test_average_purchase_updates_product_price_and_ignores_a_typed_lot_number(): void
    {
        app(InventoryPolicy::class)->select('moving_weighted_average');
        $supplier = Supplier::create(['name' => 'Average supplier', 'opening_balance' => 0, 'current_balance' => 0]);
        app(PurchaseController::class)->store(Request::create('/', 'POST', [
            'supplier_id' => $supplier->id, 'purchase_date' => '2026-11-01', 'bank_account_id' => $this->bank->id, 'paid_amount' => 100,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 4, 'purchase_price' => 25, 'selling_price' => 40, 'lot_number' => 'USER-LOT'],
            ],
        ]));
        $this->assertEquals(40, (float) $this->product->fresh()->selling_price);
        $this->assertNotSame('USER-LOT', ProductStock::value('lot_number'));
        $this->assertStringStartsWith('LOT-', (string) ProductStock::value('lot_number'));
    }

    public function test_cash_purchase_without_a_supplier_is_paid_from_the_bank(): void
    {
        app(PurchaseController::class)->store(Request::create('/', 'POST', [
            'purchase_date' => '2026-11-01', 'bank_account_id' => $this->bank->id, 'paid_amount' => 100,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 4, 'purchase_price' => 25],
            ],
        ]));
        $this->assertNull(DB::table('purchases')->value('supplier_id'));
        $this->assertEquals(100, (float) DB::table('purchases')->value('paid_amount'));
        $this->assertEquals(0, (float) DB::table('purchases')->value('due_amount'));
        $this->assertEquals(9900, (float) $this->bank->fresh()->current_balance);
    }

    public function test_due_purchase_without_a_supplier_is_rejected(): void
    {
        app(PurchaseController::class)->store(Request::create('/', 'POST', [
            'purchase_date' => '2026-11-01', 'paid_amount' => 0,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 4, 'purchase_price' => 25],
            ],
        ]));
        $this->assertDatabaseCount('purchases', 0);
        $this->assertEquals(10000, (float) $this->bank->fresh()->current_balance);
    }

    public function test_cash_history_survives_source_update_and_deletion(): void
    {
        $transaction = BankTransaction::create(['bank_account_id' => $this->bank->id, 'transaction_type' => 'out',
            'amount' => 100, 'category' => 'expense', 'date' => '2026-11-02', 'created_by' => $this->user->id]);
        $before = app(CashReport::class)->build('2026-11-01', '2026-11-03');
        $transaction->update(['amount' => 150]);
        $this->assertEquals($before, app(CashReport::class)->build('2026-11-01', '2026-11-03'));
        $this->assertEquals(9850, app(CashReport::class)->balance('2026-11-10'));
        $transaction->delete();
        $this->assertEquals(10000, app(CashReport::class)->balance('2026-11-10'));
        $this->assertEquals($before, app(CashReport::class)->build('2026-11-01', '2026-11-03'));
    }

    public function test_sale_retry_does_not_duplicate_stock_or_payment(): void
    {
        $this->receive('10', '100');
        $creator = app(SaleCreator::class);
        $items = [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 180]];
        $first = $creator->create($items, 360, 0, 360, 360, $this->bank->id, $this->customer->id, null, $this->user->id, '2026-11-03', 'retry-key');
        $count = DB::table('ledger_lines')->count();
        $again = $creator->create($items, 360, 0, 360, 360, $this->bank->id, $this->customer->id, null, $this->user->id, '2026-11-03', 'retry-key');
        $this->assertEquals($first->id, $again->id);
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(8, DB::table('inventory_balances')->value('quantity'));
        $this->assertEquals($count, DB::table('ledger_lines')->count());
    }

    public function test_service_revenue_and_discounted_tax_return_need_no_stock(): void
    {
        $this->product->update(['type' => 'service']);
        $sale = Sale::create([
            'invoice_no' => 'TAX-001', 'business_date' => '2026-11-03', 'customer_id' => $this->customer->id,
            'subtotal' => 200, 'discount' => 20, 'tax' => 18, 'total' => 198, 'paid' => 0, 'due' => 198, 'payment_status' => 'due', 'created_by' => $this->user->id,
        ]);
        $line = SaleItem::create(['sale_id' => $sale->id, 'product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100, 'subtotal' => 200]);
        $this->customer->update(['balance' => 198]);
        $return = app(SaleReturnPosting::class)->post([
            'sale_id' => $sale->id, 'customer_id' => $this->customer->id, 'return_date' => '2026-11-04', 'refund_status' => 'credited_to_due',
            'items' => [['sale_item_id' => $line->id, 'product_id' => $this->product->id, 'quantity' => 1]],
        ]);
        $this->assertEquals(99, $return->total_amount);
        $this->assertEquals(99, $this->customer->fresh()->balance);
        $this->assertEquals(99, $sale->fresh()->due);
        $this->assertDatabaseCount('product_stocks', 0);
        $sheet = app(FinancialReport::class)->balanceSheet('2026-11-04');
        $this->assertEquals(9, $sheet['fund_and_liabilities']['tax_payable']['period']);
        $this->assertEquals(90, $sheet['fund_and_liabilities']['net_profit']['period']);
        foreach ($sheet['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
    }

    public function test_expense_controller_keeps_bank_link_and_historical_reports(): void
    {
        $category = ExpenseCategory::create(['name' => 'Rent', 'status' => true]);
        $controller = app(ExpenseController::class);
        $controller->store(Request::create('/', 'POST', [
            'expense_category_id' => $category->id, 'bank_account_id' => $this->bank->id,
            'amount' => 100, 'description' => 'Rent', 'date' => '2026-11-02',
        ]));
        $expense = Expense::firstOrFail();
        $this->assertNotNull($expense->bank_transaction_id);
        $before = app(FinancialReport::class)->balanceSheet('2026-11-03');
        $controller->destroy($expense);
        $this->assertEquals($before, app(FinancialReport::class)->balanceSheet('2026-11-03'));
        foreach (app(FinancialReport::class)->balanceSheet('2026-11-10')['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
    }

    public function test_tenant_database_policies_and_ledger_do_not_leak(): void
    {
        $firstConnection = DB::getDefaultConnection();
        config(['database.connections.inventory_second_tenant' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        $this->artisan('migrate', ['--database' => 'inventory_second_tenant', '--path' => 'database/migrations/tenant', '--force' => true])->assertSuccessful();
        try {
            DB::setDefaultConnection('inventory_second_tenant');
            app(InventoryPolicy::class)->select('moving_weighted_average');
            $this->assertEquals('moving_weighted_average', app(InventoryPolicy::class)->current()->method);
            $this->assertEquals([], app(FinancialLedger::class)->balances('2026-11-10'));
        } finally {
            DB::setDefaultConnection($firstConnection);
            DB::purge('inventory_second_tenant');
        }
        $this->assertEquals('fifo', app(InventoryPolicy::class)->current()->method);
        $this->assertEquals(10000, app(FinancialLedger::class)->balances('2026-11-10')['cash:'.$this->bank->id]);
    }

    public function test_invoice_edit_and_cancellation_preserve_prior_statements(): void
    {
        $this->receive('10', '100');
        $sale = $this->sell('2', paid: 360);
        $before = Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals'];
        $controller = app(SaleController::class);
        $controller->update(Request::create('/', 'POST', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 180]],
            'subtotal' => 180, 'discount' => 0, 'total' => 180, 'paid' => 180,
            'customer_id' => $this->customer->id, 'bank_account_id' => $this->bank->id,
        ]), $sale->id);
        $this->assertEquals(180, $sale->fresh()->total);
        $this->assertEquals($before, Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals']);
        $this->assertEquals(9180, $this->bank->fresh()->current_balance);
        foreach (app(FinancialReport::class)->balanceSheet('2026-11-10')['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
        $controller->destroy($sale->id);
        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
        $this->assertEquals(9000, $this->bank->fresh()->current_balance);
        $this->assertEquals(10, DB::table('inventory_balances')->value('quantity'));
        $this->assertEquals($before, Product::getProductAnalysis('2026-11-01', '2026-11-03')['totals']);
        foreach (app(FinancialReport::class)->balanceSheet('2026-11-10')['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
    }

    public function test_legacy_initialization_preview_rolls_back_then_apply_is_idempotent(): void
    {
        $stockId = DB::table('product_stocks')->insertGetId([
            'product_id' => $this->product->id, 'quantity' => 10, 'total_quantity' => 10,
            'available_quantity' => 10, 'total_cost' => 1000, 'unit_cost' => 100, 'type' => 'purchase',
            'date' => '2026-11-01', 'created_by' => $this->user->id, 'created_at' => '2026-11-01', 'updated_at' => '2026-11-01',
        ]);
        DB::table('bank_transactions')->insert([
            'bank_account_id' => $this->bank->id, 'transaction_type' => 'out', 'amount' => 1000, 'running_balance' => 9000,
            'category' => 'stock_purchase', 'date' => '2026-11-01', 'created_by' => $this->user->id,
            'created_at' => '2026-11-01', 'updated_at' => '2026-11-01',
        ]);
        DB::table('bank_accounts')->where('id', $this->bank->id)->update(['current_balance' => 9000]);
        DB::table('inventory_policies')->where('id', 1)->update(['accounting_ready' => false]);
        $this->artisan('inventory:initialize', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull(ProductStock::find($stockId)->costing_method);
        $this->assertDatabaseCount('inventory_balances', 0);
        $this->artisan('inventory:initialize', ['--apply' => true])->assertSuccessful();
        $this->assertEquals('fifo', ProductStock::find($stockId)->costing_method);
        $count = DB::table('ledger_lines')->count();
        $this->artisan('inventory:initialize', ['--apply' => true])->assertSuccessful();
        $this->assertEquals($count, DB::table('ledger_lines')->count());
    }

    public function test_sales_and_customer_reports_date_later_collections_without_rewriting_sales(): void
    {
        $this->receive('10', '100');
        $sale = $this->sell('2', paid: 100);
        $before = app(SalesReporting::class)->build('2026-11-01', '2026-11-03');
        app(CustomerController::class)->addPayment(
            Request::create('/', 'POST', ['sale_id' => $sale->id, 'amount' => 50, 'bank_account_id' => $this->bank->id, 'note' => 'Collection']), $this->customer);
        $this->assertEquals($before, app(SalesReporting::class)->build('2026-11-01', '2026-11-03'));
        $period = app(SalesReporting::class)->build('2026-11-10', '2026-11-10');
        $this->assertEquals(0, $period['summary']['total_amount']);
        $this->assertEquals(50, $period['summary']['received']);
        $this->assertEquals(-50, $period['summary']['due']);
        $response = app(CustomerSalesReportController::class)->getReportData(
            Request::create('/', 'GET', ['customer_id' => $this->customer->id, 'year' => 2026, 'month' => 11]));
        $this->assertEquals(210, $response->getData(true)['monthlyTotals']['total_due']);
        $export = new SaleReportExport($period);
        $this->assertEquals(50, collect($export->array())->last()[6]);
    }

    public function test_supporting_bank_reports_share_the_cash_ledger_and_export_totals(): void
    {
        $this->receive('10', '100');
        $this->sell('2', paid: 100);
        $bank = app(BankReporting::class)->accounts('2026-11-01', '2026-11-10');
        $daily = app(BankReporting::class)->daily(2026, 11);
        $this->assertEquals(9100, $bank['summary']['total_balance']);
        $this->assertEquals(9100, $daily['currentAccountBalance']);
        $this->assertEquals(9100, app(CashReport::class)->balance('2026-11-10'));
        $this->assertEquals(100, $daily['monthTotals']['in']['total']);
        $this->assertEquals(1000, $daily['monthTotals']['out']['total']);
        $export = new BankTransactionReportExport($daily['dailyTransactions'], (float) $daily['previousMonthBalance']);
        $this->assertEquals(9100, collect($export->array())->last()[13]);
    }

    public function test_variant_lots_cannot_consume_another_variants_quantity_or_cost(): void
    {
        $a = ProductVariant::create(['product_id' => $this->product->id, 'name' => 'A', 'sku' => 'VAR-A']);
        $b = ProductVariant::create(['product_id' => $this->product->id, 'name' => 'B', 'sku' => 'VAR-B']);
        foreach ([[$a, 100], [$b, 200]] as [$variant, $cost]) {
            ProductStock::create([
                'product_id' => $this->product->id, 'product_variant_id' => $variant->id, 'quantity' => 2,
                'unit_cost' => $cost, 'total_cost' => $cost * 2, 'type' => 'purchase', 'date' => '2026-11-01', 'created_by' => $this->user->id,
            ]);
        }
        $sale = app(SaleCreator::class)->create([['product_id' => $this->product->id, 'product_variant_id' => $b->id, 'quantity' => 1, 'unit_price' => 300]],
            300, 0, 300, 0, null, $this->customer->id, null, $this->user->id, '2026-11-03');
        $this->assertEquals(200, $sale->items()->first()->cost_total);
        $this->assertEquals(2, DB::table('inventory_balances')->where('variant_key', $a->id)->value('quantity'));
        $this->assertEquals(1, DB::table('inventory_balances')->where('variant_key', $b->id)->value('quantity'));
    }

    public function test_principal_pdf_templates_render_from_the_shared_report_data(): void
    {
        $this->receive('10', '100');
        $this->sell('2', paid: 100);
        $request = Request::create('/', 'GET', ['start_date' => '2026-11-01', 'end_date' => '2026-11-10', 'locale' => 'en']);
        foreach ([ReceiptPaymentController::class, BalanceSheetController::class,
            IncomeExpenditureController::class, ProductAnalysisReportController::class] as $controller) {
            $response = app($controller)->downloadPdf($request);
            $this->assertStringStartsWith('%PDF', $response->getContent(), $controller);
        }
    }

    public function test_standalone_loan_movements_balance_without_becoming_income(): void
    {
        $controller = app(BankTransactionController::class);
        foreach ([['in', 1000], ['out', 200]] as [$direction, $amount]) {
            $controller->store(Request::create('/', 'POST', [
                'bank_account_id' => $this->bank->id, 'transaction_type' => $direction, 'amount' => $amount,
                'date' => '2026-11-03', 'counter_account' => 'loan_payable', 'classification_reason' => 'Loan principal reference L1',
            ]));
        }
        $sheet = app(FinancialReport::class)->balanceSheet('2026-11-03');
        $this->assertEquals(10800, $sheet['property_and_assets']['bank_balance']['period']);
        $this->assertEquals(0, $sheet['fund_and_liabilities']['net_profit']['period']);
        $this->assertEquals(800, collect($sheet['fund_and_liabilities']['rows'])->firstWhere('name', 'Loans payable')['amount']);
        foreach ($sheet['reconciliation'] as $name => $difference) {
            $this->assertEquals(0, $difference, $name);
        }
    }

    public function test_legacy_standalone_cash_can_be_explicitly_classified_before_reconciliation(): void
    {
        $id = DB::table('bank_transactions')->insertGetId([
            'bank_account_id' => $this->bank->id, 'transaction_type' => 'in', 'amount' => 1000, 'running_balance' => 11000,
            'category' => 'other', 'date' => '2026-11-01', 'created_by' => $this->user->id,
            'created_at' => '2026-11-01', 'updated_at' => '2026-11-01',
        ]);
        DB::table('inventory_policies')->where('id', 1)->update(['accounting_ready' => false]);
        DB::table('bank_accounts')->where('id', $this->bank->id)->update(['current_balance' => 11000]);
        $this->artisan('accounting:classify-cash', ['transaction' => $id, 'account' => 'capital', '--reason' => 'Verified opening capital deposit'])->assertSuccessful();
        $this->assertNull(BankTransaction::find($id)->counter_account);
        $this->artisan('accounting:classify-cash', ['transaction' => $id, 'account' => 'capital', '--reason' => 'Verified opening capital deposit', '--apply' => true])->assertSuccessful();
        $this->artisan('inventory:initialize', ['--apply' => true])->assertSuccessful();
        $this->assertEquals(11000, app(CashReport::class)->balance('2026-11-10'));
    }

    public function test_business_date_uses_tenant_timezone_and_restores_process_timezone(): void
    {
        Carbon::setTestNow('2026-11-10 20:00:00 UTC');
        $before = date_default_timezone_get();
        $response = app(ApplyBusinessTimezone::class)->handle(
            Request::create('/admin/settings'), fn () => response()->json(['date' => now()->toDateString()]));
        $this->assertEquals('2026-11-11', $response->getData(true)['date']);
        $this->assertEquals($before, date_default_timezone_get());
    }

    public function test_repeated_partial_credits_settle_original_cents_without_overrefunding_tax(): void
    {
        $this->product->update(['type' => 'service']);
        $sale = Sale::create([
            'invoice_no' => 'CENTS-001', 'business_date' => '2026-11-03', 'customer_id' => $this->customer->id,
            'subtotal' => '0.04', 'discount' => '0.00', 'tax' => '0.02', 'total' => '0.06', 'paid' => 0, 'due' => '0.06', 'payment_status' => 'due', 'created_by' => $this->user->id,
        ]);
        $line = SaleItem::create(['sale_id' => $sale->id, 'product_id' => $this->product->id, 'quantity' => 4, 'unit_price' => '0.01', 'subtotal' => '0.04', 'discount_amount' => '0.00', 'tax_amount' => '0.02']);
        $this->customer->update(['balance' => '0.06']);
        for ($i = 0; $i < 4; $i++) {
            app(SaleReturnPosting::class)->post([
                'sale_id' => $sale->id, 'customer_id' => $this->customer->id, 'return_date' => '2026-11-10', 'refund_status' => 'credited_to_due',
                'items' => [['sale_item_id' => $line->id, 'product_id' => $this->product->id, 'quantity' => 1]],
            ]);
            foreach (app(FinancialReport::class)->balanceSheet('2026-11-10')['reconciliation'] as $name => $difference) {
                $this->assertEquals(0, $difference, $name);
            }
        }
        $this->assertEquals('0.00', $this->customer->fresh()->balance);
        $this->assertEquals(0.06, SaleReturn::sum('total_amount'));
        $this->assertEquals(0, app(FinancialReport::class)->balanceSheet('2026-11-10')['fund_and_liabilities']['tax_payable']['period']);
    }

    public function test_supporting_report_pdf_exports_render_the_shared_structures(): void
    {
        $this->receive('10', '100');
        $this->sell('2', paid: 100);
        $request = Request::create('/', 'GET', ['from_date' => '2026-11-01', 'to_date' => '2026-11-10', 'year' => 2026, 'month' => 11, 'locale' => 'en']);
        foreach ([BankReportController::class, BankTransactionReportController::class,
            SaleReportController::class, ProductStockReportController::class] as $controller) {
            $response = app($controller)->downloadPdf($request);
            $this->assertStringStartsWith('%PDF', $response->getContent(), $controller);
        }
    }

    public function test_legacy_deleted_history_is_not_silently_omitted_by_initialization(): void
    {
        DB::table('product_stocks')->insert([
            'product_id' => $this->product->id, 'quantity' => 1, 'available_quantity' => 1,
            'unit_cost' => 100, 'total_cost' => 100, 'type' => 'purchase', 'date' => '2026-11-01',
            'created_by' => $this->user->id, 'created_at' => '2026-11-01', 'updated_at' => '2026-11-02', 'deleted_at' => '2026-11-02',
        ]);
        DB::table('inventory_policies')->where('id', 1)->update(['accounting_ready' => false]);
        $before = DB::table('ledger_lines')->count();
        $this->artisan('inventory:initialize', ['--apply' => true])->assertFailed();
        $this->assertDatabaseCount('inventory_balances', 0);
        $this->assertEquals($before, DB::table('ledger_lines')->count());
        $this->assertFalse((bool) DB::table('inventory_policies')->where('id', 1)->value('accounting_ready'));
    }
}
