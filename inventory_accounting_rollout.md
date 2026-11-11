# TrustCash inventory/accounting implementation and rollout

## Implemented behavior

- Tenant Settings selects FIFO or Moving Weighted Average before inventory is posted. Policy is stored in the isolated tenant database, without a global settings fallback. Ordinary method changes are locked after posting.
- Every receipt has an internal lot identity, receipt cost, remaining physical quantity, and a snapshotted suggested selling price. Purchases allocate invoice discounts, shipping, and non-recoverable tax to receipt costs using decimal arithmetic and deterministic residual allocation.
- FIFO consumes older layers; moving average values each sale from the remaining pool at posting time. Later receipts never recost an earlier sale. Variant pools are isolated.
- POS uses available lots and their suggested prices, splits cart rows when lots differ, permits an actual customer selling price, and preserves lot IDs through offline sync. Sale/return request keys prevent repeated requests from creating duplicate postings. Pending-order approval locks the order and uses its own request key.
- Sale costs, invoice discount/tax allocations, physical allocations, and return entitlements are persisted. Partial returns restore original allocated cost. Cumulative cent rounding prevents repeated small credits from over-refunding the original revenue or tax.
- The balanced journal is append-only. Source updates produce current dated reversals/replacements. New posted stock cannot be edited/deleted, and legacy repair commands cannot overwrite valued stock. Receipt cancellation is restricted to a latest untouched receipt; paid or consumed purchases require a reviewed supplier-return/credit workflow.
- Product Analysis, Receipt and Payments, Income and Expenditure, Balance Sheet, Stock Report, Bank Report, Daily Bank Transactions, Sales Report, and Customer Sales/Ledger use the shared valuation/journal services. Their screen/PDF/Excel controllers share report data.
- Historical receivables, payables, bank balances, asset values, revenue, and COGS come from effective-dated journal entries. Current subledger projections are reconciled separately. Inactive/archived parties and accounts remain reportable.
- Balance Sheet shows an as-of date, advances/credits, taxes, loans, retained earnings, and reconciliation differences. It does not insert a balancing plug. Sales/customer reports distinguish dated billed amounts, collections, and receivable movement.
- Standalone cash entry requires a purpose and reason: capital, loan principal, other income, or operating expense. Source-linked cash should be corrected through its invoice/expense/payment document.
- Business timezone is tenant-specific. Fresh tenants default to Asia/Dhaka; migration preserves the configured application timezone for existing records. Effective dates already stored are not shifted by changing timezone.

## Validation completed in this workspace

The focused inventory/accounting suite includes both methods, historical stability, split layers, variants, fractional quantities, over-return prevention, repeated return rounding, partial payments, invoice corrections/cancellation, purchase landed costs, method cutover, tenant database isolation, cash classifications, migration preview/apply/retry, supporting reports, Excel amounts, and real PDF rendering of the four principal reports.

Latest focused SQLite result: **48 tests passed, 367 assertions**. The isolated MySQL run passed **47 tests, 364 assertions**; the subsequent preserved-invoice deletion guard was also checked by the SQLite suite. Coverage includes immutable legacy records, both initial choices, historical reports, archived stock, preview/apply/retry, separate tenant schemas, atomic interruption rollback, preserved bank/customer balances, old-invoice returns, fractional variants, and consistent invoice costs even when a legacy stock movement is missing. Eight report PDF outputs rendered in the accounting suite. Production frontend build and changed-PHP syntax checks passed.

Run safely against an isolated database:

```powershell
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan test --filter='InventoryAccountingTest|ProductAnalysisTest|QuickCreateTest|LegacyInventoryCarryForwardTest'
```

This environment's existing full test suite also has unrelated authentication/profile/registration setup failures, including factories that omit required `users.role_id`. Do not treat the whole suite as green. SQLite and isolated MySQL tests validate calculations and compatibility. Real tenant data and production contention still require staging validation.

Production assets: `npm.cmd run build`. Formatting: `php vendor/bin/pint --dirty`.

## Existing-data carry-forward (2026-11-11 fix)

The earlier release read only `inventory_balances` even for unmigrated historical stock, and blocked reports behind strict reconstruction. The compatibility cutover now retains the original business records and restores usable balances and reports.

- Apply central and tenant migrations, including `2026_11_11_000001_add_legacy_inventory_carry_forward.php`.
- Existing tenant purchases, sales, returns, bank entries, quantities, prices, due amounts and soft-deleted records are not rewritten or removed.
- Each product/variant's active historical stock quantity and existing purchase-weighted average carrying value become its opening pool. A zero-movement `legacy_opening` record provides one physical opening lot without adding the same quantity twice. Original stock movements remain in the history.
- FIFO starts with that opening lot, then creates separate lots for later receipts. Average continues from the same preserved quantity and value. Settings can choose either method until the first new stock posting; later changes still require a prospective cutover.
- Legacy reporting costs and sale snapshots are stored separately. Later purchases do not recost them. Existing uniquely attributable returns count against the original invoice entitlement, and new returns restore its frozen legacy cost. Ambiguous old return attribution requires verification before another return; reports remain readable.
- Historical journal entries are built from available active source documents; archived history remains archived. Explicit carry-forward records preserve the existing stored customer, supplier and bank balances where the available source documents differ. Those differences go to opening equity, with the stored value, reconstructed value, difference and date shown on Balance Sheet, PDF and Excel. They are not revenue/expenses or a claim that missing historical evidence has been recovered. Unclassified clearing differences remain visible.
- Every tenant has its own transaction and policy lock. Carry-forward is repeatable and does not run on an already ready tenant. A partially reconstructed/valued tenant is rejected without overwriting its inventory.
- Authenticated admin access automatically completes an outstanding carry-forward once the schema exists. Product quantity also falls back to original active movements when no balance projection exists. GET validation failures render a visible explanation; flash/form errors are visible in the admin layout.

Back up and test restoration on the actual server before deployment. Stop posting during the rollout and preview a restored staging tenant first:

```text
php artisan migrate --force
php artisan tenants:migrate --force
php artisan inventory:carry-forward --all-tenants --dry-run
php artisan inventory:carry-forward --all-tenants --apply
```

For one tenant, replace `--all-tenants` with `--tenant=TENANT_ID`. Preview is the default and rolls back all generated rows. A standalone installation with no tenants omits the scope option. Missing tenant databases are reported, never created empty or silently replaced. Inspect the command exit status and every tenant's totals.

The repository deployment workflow enters maintenance mode, runs both migration sets, applies carry-forward to all tenants, caches the application and reopens traffic only on success (`set -e`). A failure leaves maintenance enabled for investigation; do not run `artisan up` until it is resolved. This is per-tenant atomicity: previously successful tenants are retained if a later tenant fails, and rerunning skips those completed tenants.

Check Product/Stock/POS quantities, opening lots, customer/supplier due, bank balance, the four principal reports and exports. Where old records were edited/deleted, past historical accounting cannot be certified from unavailable versions; preserved current balances are explicit evidence of the existing state, not invented historical transactions. Keep issued statements and review reconciliation differences.

No production tenant database was changed in this session. The local central database lists five tenants, but four corresponding databases are missing and the available demo database has no stock or the new inventory schema. Consequently this workspace cannot verify or repair the user's actual production records directly. Tests run only on isolated SQLite and a newly created disposable MySQL database, which was removed afterward.

The strict `inventory:initialize --tenant=TENANT_ID --dry-run` historical replay remains available as an alternative for a fully evidenced, unreconstructed database. It intentionally rejects deleted/ambiguous history; it is not required for the carry-forward path and safely reports an already carried-forward tenant. Do not run competing initialization processes during rollout.

Carry-forward evidence is retained by migration rollback. Rollback before reopening requires the verified database backup and previous application version. After new business postings, use forward corrections; never restore an old backup over new transactions.

## Later policy change

The Settings dropdown cannot rewrite posted history. A controlled prospective cutover uses an unused accounting day after all posted activity, preserves total carrying value, writes policy history, and locks the preceding period:

```text
php artisan inventory:change-method fifo --tenant=TENANT_ID --date=YYYY-MM-DD --reason="Reviewed accounting policy change"
php artisan inventory:change-method fifo --tenant=TENANT_ID --date=YYYY-MM-DD --reason="Reviewed accounting policy change" --apply
```

Replace `fifo` with `moving_weighted_average` for the other direction. Preview is the default. Moving average to FIFO creates transition carrying values on remaining lots, rather than replacing book value with old purchase prices. Applicable statutory restatement/disclosure requirements still need the business's accounting review.

## Deliberate boundaries

- Physical picking defaults to FIFO. Under FIFO a manually supplied lot must follow FIFO order; FEFO, independent physical picking, serialized inventory and warehouse transfer/reservation modules were not added.
- Units are valued in the product's base unit. A different purchase unit is rejected until an explicit unit-conversion definition exists.
- Inventory tax allocation currently treats purchase tax as non-recoverable. Recoverable input-VAT configuration and statutory tax filing are separate work. Existing posted sale tax is journaled and its returns are credited at the original allocation; the POS was not turned into a new tax-calculation module.
- A tax-bearing invoice is corrected with a linked return/credit rather than silently edited by the ordinary invoice editor. A sale with later collections or existing returns also uses controlled returns instead of deleting its history.
- A general supplier-return/credit module for consumed or paid receipts, inventory NRV/write-down UI, and a full legacy opening-trial-balance import wizard were not introduced. Unsafe cancellations are rejected.
- Historical reports are stable under later receipts and current dated corrections. Posting an earlier-dated financial transaction in an open period intentionally changes that period; locked periods reject it. Earlier-dated inventory insertion before subsequent stock activity is rejected.

These boundaries are explicit so an unsupported workflow cannot silently produce a plausible but incorrect financial statement.
