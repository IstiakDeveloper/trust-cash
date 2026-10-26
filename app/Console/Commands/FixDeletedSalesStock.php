<?php

namespace App\Console\Commands;

use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDeletedSalesStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocks:fix-deleted-sales 
                            {--tenant= : The tenant ID to fix (e.g. dokandaricombd)}
                            {--all : Run for all tenants}
                            {--dry-run : Preview changes without applying them to the database}
                            {--recalculate : Recalculate running available_quantity for affected products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix corrupted stock history and restore available stock for soft-deleted sales';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $runAll = $this->option('all');
        $isDryRun = (bool) $this->option('dry-run');
        $recalculate = (bool) $this->option('recalculate');

        if ($isDryRun) {
            $this->warn('🔍 [DRY-RUN MODE] No changes will be saved to the database.');
            $this->newLine();
        }

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant [{$tenantId}] not found!");

                return Command::FAILURE;
            }

            $this->info("Processing tenant: {$tenant->id} ({$tenant->name})");
            $tenant->run(fn () => $this->fixTenant($isDryRun, $recalculate));
        } elseif ($runAll) {
            $tenants = Tenant::all();
            $this->info("Found {$tenants->count()} tenants. Processing all...");

            foreach ($tenants as $tenant) {
                $this->newLine();
                $this->info("=== Processing tenant: {$tenant->id} ({$tenant->name}) ===");
                $tenant->run(fn () => $this->fixTenant($isDryRun, $recalculate));
            }
        } elseif (function_exists('tenancy') && tenancy()->initialized) {
            $this->fixTenant($isDryRun, $recalculate);
        } else {
            $this->error('Please specify a tenant with --tenant=<id> or use --all to run for all tenants.');
            $this->line('Example: php artisan stocks:fix-deleted-sales --tenant=dokandaricombd --dry-run');

            return Command::INVALID;
        }

        $this->newLine();
        $this->info('✓ Fix operation finished.');

        return Command::SUCCESS;
    }

    /**
     * Fix deleted sales stock discrepancies inside current tenant context.
     */
    protected function fixTenant(bool $isDryRun, bool $recalculate): void
    {
        // 1. Find all soft-deleted sales with their items
        $deletedSales = Sale::onlyTrashed()->with(['saleItems.product'])->get();

        if ($deletedSales->isEmpty()) {
            $this->line('No soft-deleted sales found for this tenant.');

            return;
        }

        $this->info("Found {$deletedSales->count()} soft-deleted sales.");

        $fixedSalesCount = 0;
        $affectedProductIds = [];

        foreach ($deletedSales as $sale) {
            // Check if this sale already has a sale_reversal record
            $alreadyReversed = ProductStock::where('type', 'sale_reversal')
                ->where('note', 'like', "%{$sale->invoice_no}%")
                ->exists();

            if ($alreadyReversed) {
                $this->line("• Invoice #{$sale->invoice_no}: Already has reversal record. Skipping.");

                continue;
            }

            $this->warn("• Invoice #{$sale->invoice_no} (Deleted at: {$sale->deleted_at}): Missing stock reversal.");

            DB::beginTransaction();
            try {
                foreach ($sale->saleItems as $item) {
                    $productId = $item->product_id;
                    $quantity = (float) $item->quantity;
                    $affectedProductIds[$productId] = true;

                    // Step A: Decrement the falsely added quantity from the oldest stock record (due to old firstOrCreate bug)
                    $oldestStock = ProductStock::where('product_id', $productId)
                        ->orderBy('id', 'asc')
                        ->first();

                    if ($oldestStock) {
                        $this->line("   - Product #{$productId} [{$item->product?->name}]: Oldest record #{$oldestStock->id} was falsely incremented by {$quantity}. Adjusting back.");
                        if (! $isDryRun) {
                            $oldestStock->decrement('quantity', $quantity);
                        }
                    }

                    // Step B: Get latest stock record with row lock to calculate updated available quantity
                    $latestStock = ProductStock::where('product_id', $productId)
                        ->orderBy('id', 'desc')
                        ->first();

                    $beforeQty = $latestStock ? (float) $latestStock->available_quantity : 0;
                    $afterQty = $beforeQty + $quantity;

                    $this->info("   + Product #{$productId} [{$item->product?->name}]: Creating sale_reversal (+{$quantity} qty). Available: {$beforeQty} -> {$afterQty}");

                    if (! $isDryRun) {
                        // Create proper audit log entry
                        ProductStock::create([
                            'product_id' => $productId,
                            'quantity' => $quantity, // positive for reversal
                            'available_quantity' => $afterQty,
                            'type' => 'sale_reversal',
                            'date' => $sale->deleted_at ? $sale->deleted_at->toDateString() : now()->toDateString(),
                            'unit_cost' => $latestStock ? $latestStock->unit_cost : 0,
                            'total_cost' => $latestStock ? ($latestStock->unit_cost * $quantity) : 0,
                            'note' => "সেল ইনভয়েস #{$sale->invoice_no} মুছে ফেলার কারণে স্টক সমন্বয় (স্বয়ংক্রিয় ফিক্স)",
                            'created_by' => $sale->created_by,
                            'created_at' => $sale->deleted_at ?? now(),
                            'updated_at' => $sale->deleted_at ?? now(),
                        ]);

                        StockMovement::create([
                            'product_id' => $productId,
                            'reference_type' => 'sale_delete',
                            'reference_id' => $sale->id,
                            'quantity' => $quantity,
                            'before_quantity' => $beforeQty,
                            'after_quantity' => $afterQty,
                            'type' => 'in',
                            'created_by' => $sale->created_by,
                            'created_at' => $sale->deleted_at ?? now(),
                            'updated_at' => $sale->deleted_at ?? now(),
                        ]);
                    }
                }

                if (! $isDryRun) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }

                $fixedSalesCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("   Failed to fix invoice #{$sale->invoice_no}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$fixedSalesCount} sales for stock reversal.");

        // Step C: Optional recalculation of running available_quantity for affected products
        if ($recalculate && ! empty($affectedProductIds)) {
            $this->newLine();
            $this->info('Recalculating running available_quantity for affected products...');

            foreach (array_keys($affectedProductIds) as $productId) {
                $stocks = ProductStock::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $running = 0.0;
                $updatedRows = 0;

                foreach ($stocks as $st) {
                    $running += (float) $st->quantity;

                    if (abs((float) $st->available_quantity - $running) > 0.001) {
                        if (! $isDryRun) {
                            $st->available_quantity = $running;
                            $st->saveQuietly();
                        }
                        $updatedRows++;
                    }
                }

                $this->line(" • Product #{$productId}: Checked {$stocks->count()} entries, updated {$updatedRows} running balance snapshots (Final Available: {$running}).");
            }
        }
    }
}
