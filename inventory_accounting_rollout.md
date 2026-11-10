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

Latest focused result: 31 tests passed, 228 assertions; eight report PDF outputs were rendered. Production asset build passed.

Run safely against an isolated database:

```powershell
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan test --filter='InventoryAccountingTest|ProductAnalysisTest'
```

This environment's existing full test suite also has unrelated authentication/profile/registration setup failures, including factories that omit required `users.role_id`. Do not treat the whole suite as green. SQLite tests validate calculations and isolation; production MySQL contention, deployment, and real tenant data still require staging validation.

Production assets: `npm.cmd run build`. Formatting: `php vendor/bin/pint --dirty`.

## Existing-data rollout

No live/production database was migrated or reconstructed during implementation. The following is an operator runbook, not a record of commands already run against live data.

1. Back up central and tenant databases and test restoration. Export issued historical statements before deploying this version. Use a restored staging clone first; record the agreed costing policy and treatment of historical differences.
2. Stop posting while migrating and initializing. Existing tenants become `accounting_ready=false`; new financial postings/reports are intentionally blocked until reconstruction reconciles. Do not resume traffic for an uninitialized database.
3. Apply the standalone/central migrations and tenant migrations on staging, then repeat the reviewed process in the deployment window:

```text
php artisan migrate --force
php artisan tenants:migrate --tenants=TENANT_ID --force
```

4. Select the initial method in that tenant's Settings. Existing tenants initially retain a moving-average policy selection; this is not a claim that the old retrospective purchase-average calculation was correct moving-average accounting.
5. Preview reconstruction for the tenant:

```text
php artisan inventory:initialize --tenant=TENANT_ID --dry-run
```

The command rolls back its work by default. It shows reconstructed inventory/earnings/cash and refuses ambiguous sale links, unsupported legacy deleted history, missing return/reversal evidence, incomplete costs, unexplained clearing, or subledger differences. It does not fabricate lots or silently treat unknown cost as zero. A failure requires source reconciliation or a separately reviewed opening-balance cutover; bypassing the validator is not a migration procedure.

6. For an evidenced standalone cash movement with no owning invoice/expense/fund, explicitly classify it before reconstruction. Supported accounts are `capital`, `loan_payable`, `other_income:0`, and `expense:0`. This tool is not for replacing source document accounting:

```text
php artisan accounting:classify-cash TRANSACTION_ID capital --tenant=TENANT_ID --reason="Verified capital deposit reference ..."
php artisan accounting:classify-cash TRANSACTION_ID capital --tenant=TENANT_ID --reason="Verified capital deposit reference ..." --apply
```

7. Review the preview against stock count, supplier/customer statements, bank statements and the opening trial balance. Existing edited records may lack earlier versions; a successful replay is not proof of unavailable historical facts. Legacy deleted financial records require evidence-backed reconstruction or a separately prepared cutover and are explicitly rejected by automatic replay.
8. Apply only after that review:

```text
php artisan inventory:initialize --tenant=TENANT_ID --apply
```

For a genuine standalone installation with no tenants, omit `--tenant`. On a multi-tenant installation the commands refuse to guess the target database. Repeat the audited process for each tenant before reopening posting.

9. Check all Balance Sheet reconciliation lines, bank balances, lot quantities/value, customer and supplier controls, and report/export totals. Pilot on the restored tenant clone and validate two tills competing for the last stock on MySQL. Verify duplicate-request behavior through the actual HTTP/offline path.
10. Resume posting only when differences are explained and resolved. Before any new posting, rollback can restore the tested backup and previous application version. After posting resumes, use a reviewed forward correction/recovery procedure; never restore an old backup over new business transactions. Migration `down` is not a data-preserving application rollback once journals exist.

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
