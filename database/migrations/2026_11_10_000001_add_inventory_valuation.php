<?php

use App\Services\CashTransactionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['product_stocks', 'sales'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'bank_account_id')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->unsignedBigInteger('bank_account_id')->nullable());
            }
        }
        foreach (['expenses', 'extra_incomes', 'funds'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->unsignedBigInteger('bank_transaction_id')->nullable()->index());
        }
        Schema::create('inventory_policies', function (Blueprint $table) {
            $table->id();
            $table->string('method');
            $table->boolean('locked')->default(false);
            $table->boolean('accounting_ready')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('locked_through')->nullable();
            $table->timestamps();
        });
        DB::table('inventory_policies')->insert([
            'id' => 1, 'method' => DB::table('product_stocks')->exists() ? 'moving_weighted_average' : 'fifo',
            'locked' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $hasLegacy = false;
        foreach (['product_stocks', 'sales', 'bank_accounts', 'expenses', 'extra_incomes', 'funds', 'purchases', 'suppliers', 'fixed_asset_items'] as $table) {
            if (DB::table($table)->exists()) {
                $hasLegacy = true;
            }
        }
        DB::table('inventory_policies')->where('id', 1)->update(['accounting_ready' => ! $hasLegacy]);
        if (! DB::table('settings')->where('key', 'business_timezone')->exists()) {
            DB::table('settings')->insert([
                'key' => 'business_timezone', 'value' => $hasLegacy ? config('app.timezone', 'UTC') : 'Asia/Dhaka',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::table('sales', function (Blueprint $table) {
            $table->date('business_date')->nullable();
            $table->string('request_key', 100)->nullable()->unique();
            $table->string('request_hash', 64)->nullable();
        });
        foreach (DB::table('sales')->get() as $sale) {
            DB::table('sales')->where('id', $sale->id)->update(['business_date' => substr($sale->created_at, 0, 10)]);
        }
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            // Zero identifies the base product; avoids nullable unique-key ambiguity.
            $table->unsignedBigInteger('variant_key')->default(0);
            $table->decimal('quantity', 20, 6)->default(0);
            $table->decimal('value', 24, 6)->default(0);
            $table->date('last_effective_date')->nullable();
            $table->unique(['product_id', 'variant_key']);
        });
        Schema::create('inventory_policy_changes', function (Blueprint $table) {
            $table->id();
            $table->string('old_method');
            $table->string('new_method');
            $table->date('effective_from');
            $table->text('reason');
            $table->json('opening_balances');
            $table->timestamp('created_at');
        });
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('quantity', 20, 6)->change();
            $table->decimal('total_quantity', 20, 6)->default(0)->change();
            $table->decimal('total_cost', 24, 6)->change();
            $table->decimal('unit_cost', 24, 8)->change();
            $table->string('costing_method')->nullable();
            $table->decimal('inventory_value', 24, 6)->nullable();
            $table->decimal('lot_remaining_quantity', 20, 6)->nullable();
            $table->decimal('lot_remaining_value', 24, 6)->nullable();
            $table->string('lot_number')->nullable();
            $table->decimal('selling_price', 18, 2)->nullable();
            $table->unsignedBigInteger('sale_item_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_item_id')->nullable()->index();
            $table->unsignedBigInteger('source_stock_id')->nullable()->index();
            $table->unsignedBigInteger('sale_return_item_id')->nullable()->index();
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 20, 6)->change();
            $table->decimal('cost_total', 24, 6)->nullable();
            $table->decimal('discount_amount', 20, 2)->nullable();
            $table->decimal('tax_amount', 20, 2)->nullable();
            $table->string('costing_method')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
        });
        Schema::table('purchase_items', fn (Blueprint $table) => $table->decimal('quantity', 20, 6)->change());
        Schema::table('pending_sale_items', fn (Blueprint $table) => $table->decimal('quantity', 20, 6)->change());
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_item_id')->nullable()->index();
            $table->decimal('quantity', 20, 6)->change();
            $table->decimal('cost_total', 24, 6)->nullable();
        });
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->string('request_key', 100)->nullable()->unique();
            $table->string('request_hash', 64)->nullable();
        });
        Schema::create('inventory_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id')->index();
            $table->unsignedBigInteger('lot_id')->index();
            $table->decimal('quantity', 20, 6);
            $table->decimal('cost', 24, 6);
            $table->decimal('returned_quantity', 20, 6)->default(0);
            $table->decimal('returned_cost', 24, 6)->default(0);
            $table->unique(['stock_id', 'lot_id']);
        });
        Schema::create('sale_accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('date')->index();
            $table->decimal('quantity', 20, 6);
            $table->decimal('gross', 24, 6);
            $table->decimal('discount', 24, 6);
            $table->decimal('net', 24, 6);
            $table->decimal('tax', 24, 6)->default(0);
            $table->decimal('cost', 24, 6)->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('category')->nullable()->index();
            $table->string('transfer_group')->nullable()->index();
            $table->string('counter_account')->nullable();
            $table->text('classification_reason')->nullable();
        });
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->date('opened_on')->nullable();
        });
        foreach (DB::table('bank_accounts')->get() as $account) {
            $first = DB::table('bank_transactions')->where('bank_account_id', $account->id)->min('date');
            $opened = substr($account->created_at, 0, 10);
            DB::table('bank_accounts')->where('id', $account->id)->update(['opened_on' => $first && $first < $opened ? $first : $opened]);
        }
        DB::table('bank_transactions')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('bank_transactions')->where('id', $row->id)->update([
                    'category' => CashTransactionCategory::infer($row->description),
                ]);
            }
        });
        Schema::create('ledger_documents', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('version')->default(1);
            $table->string('fingerprint', 64);
            $table->json('lines');
            $table->unique(['source_type', 'source_id']);
        });
        Schema::create('ledger_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->index();
            $table->unsignedInteger('version');
            $table->date('date')->index();
            $table->string('account')->index();
            $table->string('category')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            // Positive = debit; negative = credit. Each document version balances to zero.
            $table->decimal('amount', 24, 6);
            $table->boolean('reversal')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach (['expenses', 'extra_incomes', 'funds'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('bank_transaction_id'));
        }
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['business_date', 'request_key', 'request_hash']));
        Schema::dropIfExists('ledger_lines');
        Schema::dropIfExists('ledger_documents');
        Schema::table('bank_transactions', fn (Blueprint $table) => $table->dropColumn(['category', 'transfer_group', 'counter_account', 'classification_reason']));
        Schema::table('bank_accounts', fn (Blueprint $table) => $table->dropColumn('opened_on'));
        Schema::dropIfExists('sale_accounting_entries');
        Schema::table('sale_returns', fn (Blueprint $table) => $table->dropColumn(['request_key', 'request_hash']));
        Schema::dropIfExists('inventory_allocations');
        Schema::table('sale_return_items', fn (Blueprint $table) => $table->dropColumn(['sale_item_id', 'cost_total']));
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn(['cost_total', 'costing_method', 'lot_id', 'discount_amount', 'tax_amount']));
        Schema::table('product_stocks', fn (Blueprint $table) => $table->dropColumn([
            'costing_method', 'inventory_value', 'lot_remaining_quantity', 'lot_remaining_value',
            'lot_number', 'selling_price', 'sale_item_id', 'purchase_item_id', 'source_stock_id', 'sale_return_item_id',
        ]));
        // Retain widened numeric columns to avoid truncating posted fractional data.
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_policy_changes');
        Schema::dropIfExists('inventory_policies');
    }
};
