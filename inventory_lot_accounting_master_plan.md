# TrustCash: lot inventory and reconciled financial reports

Date: 2026-11-10. Status: core tenant-selectable costing and shared accounting/report implementation added. See `inventory_accounting_rollout.md` for implemented behavior, validation, deployment commands, and explicit remaining module boundaries. Live data has not been migrated. Updated 2026-11-11: the compatibility fix preserves original records, carries existing stock/value into one opening lot per product/variant, supports either initial tenant choice and restores reports without requiring a strict historical replay. The rollout document describes the carry-forward procedure and its validation.

## 1. Recommended policy

Use perpetual inventory, receipt-level lots, immutable sale-cost allocations, and a double-entry accounting ledger. Support BOTH FIFO and perpetual Moving Weighted Average as tenant-selectable costing policies. Recommend FIFO as the initial default, but each tenant chooses its policy in Settings before posting inventory transactions. Track physical batches separately from accounting valuation so physical picking does not silently change the financial costing policy. Specific identification for genuinely distinct/serialized items is a possible future scope, not a third selectable method in this implementation.

### Tenant setting: two supported systems

- Settings > Inventory > Costing Method: `FIFO` or `Moving Weighted Average` (`moving_weighted_average`). Both support physical lots and lot-specific suggested selling prices. Method selection changes cost recognition and valuation, not pricing permissions or whether lots are recorded.
- Apply one policy consistently to the tenant's interchangeable inventory in the initial release. Store tenant-scoped effective-dated policy history, actor, reason, and policy version on valuation postings. Resolve settings only through the authenticated tenant context; do not accept a client-supplied tenant identity or global fallback that changes an existing tenant's method.
- Before the first posting, an authorized tenant administrator can freely select the method. After postings exist, disable direct dropdown changes. Provide a controlled prospective cutover workflow at an agreed open-period boundary: reconcile and lock the old period, preview opening quantity/value and report impact, record the new effective-dated policy, and preserve prior postings and reports. A policy change may require separate retrospective reporting treatment under the applicable accounting framework; do not imply that a software cutover alone satisfies statutory reporting requirements.
- FIFO to average: carry remaining quantities and total carrying value into the new average pool. Average to FIFO: establish explicitly labelled transition layers from verified physical lots, allocating the existing carrying value deterministically and preserving the total; do not substitute original purchase costs and create an unexplained gain/loss. Preview and record rounding and any separately justified valuation adjustment. Backdated transactions across a policy boundary are blocked from ordinary posting.
- FIFO: consume eligible accounting layers chronologically and snapshot allocations and COGS. Moving average: before a new receipt, carry forward remaining quantity Q and value V; for receipt quantity q and eligible cost C, new average = (V + C) / (Q + q). A sale consumes quantity at the current posted pool average and snapshots its COGS. Later receipts never recost earlier sales. This is not an all-time purchase average or a report-period average.
- Average pools are tenant/product/variant scoped initially. Warehouse movements do not recalculate the average in that design. If independently valued warehouses are later required, specify transfer-at-carrying-cost rules and explicit pool scopes before enabling them.
- Lot-specific physical availability and suggested prices work identically under both policies. Under moving average, a lot's original purchase cost is informational; its remaining quantity times original purchase cost is not the authoritative carrying value. Product Analysis must label original lot purchase cost and accounting valuation separately.
- Returns reverse the original allocated sale cost under either policy. Under moving average, a resellable return adds original returned cost and quantity back into the pool and recalculates its current average. Supplier returns and cost corrections must use a documented policy-aware valuation adjustment when original invoice cost differs from current pool carrying cost; reconcile supplier credit, inventory carrying value, and any cost difference through explicit journal entries.
- Implement a common valuation-policy interface with FIFO and MovingAverage strategies. All receipt/sale/return/reversal/migration/report paths resolve the effective policy and persist results; reports never revalue old sales using the tenant's current selection.
- Existing tenants require explicit migration mapping to their historical policy. Preserve legacy outputs as legacy where the current implementation differs from valid moving average; do not silently rename legacy retrospective averages as moving average.
- Run the complete acceptance/reconciliation suite for both policies, including tenant isolation, different simultaneous tenant settings, policy cutover in both directions, historical exports before/after change, zero stock, zero-cost receipts, fractional quantities, and returns spanning a cutover. Such returns reference original cost but post their current inventory effect under the new policy through a tested transition rule.

Weighted average is also a valid accounting method. The problem to remove is retrospective report-time costing, inconsistent calculations, and missing transaction links. Do not silently change historical accounting policy or rewrite previously issued financial statements.

Each receipt lot has its own purchase cost and suggested retail/wholesale prices. Suggested selling price is a pricing decision, independent of inventory valuation. Preserve the actual selling price, discounts, tax treatment, and cost used on every posted sale. Future purchase costs or price-list changes must not alter a posted sale.

IAS 2 reference: https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/ . It permits FIFO or weighted average for interchangeable inventory and specific identification for non-interchangeable inventory, and requires measurement at the lower of cost and net realisable value. The policy above is an architectural recommendation; applicable reporting and tax configuration must be confirmed for the business before release.

## 2. Findings from repository review

These are code-review findings, not a production-data reconciliation:

| Location | Observed behavior | Required change |
| --- | --- | --- |
| `app/Models/Product.php::calculateProductMetrics` | Uses all purchases up to report end to cost period sales; opening value uses a latest-row unit cost; negative computed inventory value is clamped to zero | Read chronological, persisted valuation entries; surface discrepancies rather than hiding them |
| `app/Services/SaleCreator.php` | Sale stock entry copies latest stock row cost; sale items have no lot allocation | Allocate cost layers atomically and snapshot COGS |
| `app/Models/SaleItem.php` | Quantity casts to integer | Align fractional quantities and base-unit conversion across purchases, sales, returns, and movements |
| `app/Traits/ManagesStock.php` | Reads latest product stock row, without variant dimension | Replace independent stock calculations with a shared inventory service |
| `app/Http/Controllers/Admin/ProductStockController.php` | Stock list uses purchase averages and stock posting updates product cost | Replace authoritative averages with remaining layer values; retain average only as a labelled analytical metric |
| `app/Http/Controllers/Admin/BalanceSheetController.php` | Historical customer due uses present customer balance; historical fixed assets use current value/status; cumulative start is hardcoded to 2024-12-29 | Use dated ledger/subledger balances and tenant opening dates |
| `app/Http/Controllers/Admin/ReceiptPaymentController.php` | Consolidation selects currently active accounts and excludes rounding by description text | Include historically relevant accounts and use typed accounting entries |
| `app/Http/Controllers/Admin/IncomeExpenditureController.php` | Profit depends on Product Analysis; displayed expense categories select currently active categories | Use ledger-based accrual results and include categories with historical activity |
| `tests/Feature/ProductAnalysisTest.php` | Existing expectations include weighted-average costing | Preserve legacy policy tests where applicable; add explicit FIFO and cutover tests |

Before implementation, map every posting path: POS, regular sales, pending-sale conversion, purchases, stock intake, imports, edits, cancellation, returns, observers, repair commands, expenses, income, funds, fixed assets, bank transfers, customer receipts, and supplier payments. No path may keep writing a parallel authoritative balance.

## 3. Data and service design

Introduce additive migrations for all applicable tenant/central schemas. Confirm connection ownership and migration conventions before writing them; never create cross-tenant relationships.

- `inventory_lots`: product, variant, warehouse/location where supported, source purchase item or opening document, lot number, receipt/effective time, supplier, optional expiry, base-unit quantity, cost components, suggested prices, and status. Internal receipt lot identity is distinct from manufacturer batch number.
- `inventory_cost_layers`: policy-specific receipt/return cost layers, original and remaining quantities, unit cost, effective sequence, source references. Physical lots and valuation layers may coincide under FIFO but must remain conceptually separable.
- `inventory_movements`: signed physical quantity, lot, business effective date, posting timestamp, transaction type, source line, actor, reversal reference. Posted entries are immutable.
- `sale_item_cost_allocations`: sale line, cost layer, allocated quantity, unit cost, total cost, policy version, reversal links. One sale line can consume multiple layers.
- `sale_item_lot_allocations`: physical batch picks where distinct from costing; preserve links for traceability and returns.
- Sale-line snapshots: actual gross revenue, allocated invoice discount, net revenue, tax, base quantity, and total COGS. Keep historical display identity for archived products.
- `journal_entries` / `journal_lines` / chart of accounts: balanced debit-credit postings, effective date, posting time, source identity, status, reversal, and unique idempotency key. Supporting customer/supplier balances derive from these entries and party links.
- Opening-balance documents, period locks, audit history, and migration reconciliation records.

Use a shared InventoryPostingService, SalePostingService, ReturnPostingService, AccountingPostingService, and ReportQueryService. Existing controllers/observers must delegate to these services. Current balances and remaining quantities are transactional projections, rebuildable from immutable records; historical reports must not rely on today's projections.

Use exact decimal arithmetic end to end, including database and PHP calculations. Choose precision from existing limits and maximum business volume; a starting proposal is quantity DECIMAL(20,6), unit cost DECIMAL(24,8), extended values DECIMAL(24,6), and currency-specific display rounding. Define rounding residual allocation explicitly. Do not use float conversions for authoritative money calculations.

## 4. Posting and user workflows

### Stock receipt

Enter supplier/invoice, business date, product/variant, quantity/unit, purchase price, discounts, eligible freight/landed cost, payment, batch/expiry, and suggested selling prices. Allocate shared landed costs deterministically using a documented value/quantity/weight basis. Recoverable tax is separate from inventory cost. Opening stock and physical adjustments are separate document types and must not manufacture a cash payment or supplier purchase.

Posting creates linked purchase items, lots, cost layers, movements, payable/cash entries in one database transaction. The same receipt must never be recognized twice through both purchase and stock-intake paths.

### Sale

Default physical selection to FIFO; optional FEFO for expiry-sensitive goods. FEFO controls picking and does not automatically change the accounting cost formula. Show lot, available quantity, suggested selling price, and editable actual selling price subject to role permissions. If a sale uses lots with different suggested prices, split visible invoice lines by lot/price unless the operator explicitly applies one customer price.

Allocate FIFO cost layers in effective-date and stable-ID order within the same tenant/product/variant/location. Lock the stock balance key first, including the empty-stock case, then lock layers in stable order. Check reservations and availability. Atomically persist invoice, allocations, movements, journal, payment, and idempotency key; roll back all of them on failure. Retry bounded deadlocks safely. Disable negative stock by default and release reservations on expiry/cancel.

Cash sale: debit Cash/Bank, credit Sales (and tax payable if applicable); debit COGS, credit Inventory. Credit sale: debit Receivable instead of cash. Later collection: debit Cash/Bank, credit Receivable; do not create revenue again. Purchases and later supplier settlements follow the corresponding Inventory/Payable/Cash entries.

### Returns, corrections, and dates

- Sales return references original sold quantities, physical lots, original discount/tax allocation, and original cost allocations. Partial and repeated returns cannot exceed the sold quantity. Resellable returns restore traceable original-cost layers with an explicit re-entry sequence; damaged goods enter quarantine and follow a write-off policy. Refund or customer credit is separate from whether cash actually moves.
- Supplier return references the original receipt and remaining stock; consumed quantities cannot simply disappear. Define whether credit reduces payable or creates supplier credit/receivable.
- Posted edit/delete becomes reversal plus replacement. A historical receipt reversal must not remove stock already consumed; resolve dependent documents or use a justified correction document.
- Default backdated posting to locked-period protection. An open-period insertion before existing sales must either be rejected in favor of a current correction or use a controlled replay that reverses/reposts dependent allocations and journals. Record changed results; never recalculate quietly when opening a report.
- Keep effective business date separate from created/posted timestamp. Use tenant business timezone and deterministic same-day ordering.
- Service products bypass physical inventory and FIFO. Their revenue and any separately configured direct costs still participate in financial reporting.

## 5. Report contracts

All report screens, PDFs, Excel downloads, dashboard cards, and supporting reports consume the same versioned report data service. Filters, tenant scope, effective dates, precision, posted/reversed handling, and totals must match. Historical archived accounts/products/categories remain reportable.

| Report | Required content and source |
| --- | --- |
| Product Analysis | Opening quantity/value; purchases; sales; customer/supplier returns; adjustments/write-offs; closing quantity/value; gross sales; discounts; returns; net sales; persisted COGS; gross profit; margin; product-to-lot-to-source drilldown |
| Receipt and Payments | Actual cash/bank receipts and payments; dated opening and closing balances; partial collections/settlements on payment dates; customer/supplier advances and refunds; fund/loan/fixed-asset cash flows; per-account transfer legs with consolidated internal transfers eliminated |
| Income and Expenditure | Accrual net revenue minus COGS, plus other earned income, minus recognized operating expenses/depreciation/finance costs; separate gross and net profit; capital, loan principal, and asset purchases excluded from operating expense |
| Balance Sheet | As-of assets, liabilities, equity/funds, and retained/current earnings; dated cash, net inventory valuation, receivables, supplier advances, fixed assets less accumulated depreciation, payables, customer advances, loans, taxes, and opening equity |

Product profit is gross profit, not final business net profit. Receipts are not automatically income; payments are not automatically expenses. Inventory purchases become COGS when goods are sold. Loan principal, capital contributions, withdrawals, transfers, and fixed-asset purchases require separate account mappings. Entity configuration determines equity/fund presentation.

Balance Sheet must use an as-of date, with optional comparative date, rather than imply that assets are monthly flows. Opening equity must balance all opening assets and liabilities, not merely bank opening balances. Inventory write-downs use a separate valuation adjustment and expense; a suggested selling price change alone does not revalue inventory.

Inventory quantity equation: opening + receipts + customer returns + positive adjustments - sales - supplier returns - negative adjustments = closing. Inventory value bridge uses the corresponding persisted entry values and separately reconciles valuation allowances. Gross profit = net revenue excluding pass-through tax - net COGS. Net profit adds other income and subtracts recognized expenses.

Mandatory reconciliation:

1. Debits equal credits for every journal and trial balance.
2. Opening cash + receipts - payments = closing cash, per account and consolidated.
3. Physical stock movements reconcile to lot balances; cost-layer value reconciles to inventory control accounts.
4. Product Analysis COGS reconciles to the general ledger and Income and Expenditure for the same product-revenue scope.
5. Customer/supplier subledgers reconcile to control accounts; advances are classified separately.
6. Net earnings roll into equity once; Assets = Liabilities + Equity.
7. Screen, PDF, and Excel match for identical report parameters and report version.

Provide a reconciliation screen with discrepancy amount and source drilldown. Never hide a mismatch with max(0), an unexplained balancing amount, or text-based transaction exclusions.

## 6. Worked acceptance example

Receive lot A: 10 units at cost 100, suggested sale 150. Receive lot B: 10 at cost 120, suggested sale 180. Sell 12: 10 A at 150 and 2 B at 180. No tax, discount, or other costs in this example.

- Revenue = 1,860; FIFO COGS = 1,240; gross profit = 620.
- Closing stock = 8 B; inventory value = 960.
- If customer pays 1,000, receipts = 1,000 and receivable = 860; recognized revenue remains 1,860.
- Return one of the original B units for full credit: sales return 180; COGS reversal 120; resellable stock becomes 9 B valued at 1,080; cumulative gross profit becomes 560.
- A later purchase at cost 200 must not alter these posted sale costs or earlier as-of statements.

## 7. Migration and rollout

Prefer an agreed cutover with verified opening documents unless historical transaction evidence supports a reliable reconstruction. Do not label fabricated lot allocations as original historical facts.

1. Back up and test restore; audit each tenant's physical quantities, purchase links, stock histories, returns, cash/bank, receivables/payables, assets, and equity. Classify missing evidence and duplicated records.
2. Agree costing policy, cutover date, pricing behavior, supported units, tax treatment, opening balances, and period-lock rules. Preserve published legacy reports and policy versions.
3. Build additive schemas and services behind tenant feature flags. Map every current writer and remove duplicate observer/controller effects.
4. Dry-run migration to either evidence-backed lots/layers or labelled legacy opening lots. Unknown costs become reconciliation exceptions for resolution; never silently zero them. Build a balanced opening trial balance with explained, reviewed corrections.
5. If replay is possible, process chronological business events with stable tie-breaking and produce an exception/change report. Historical restatement is a separately controlled operation.
6. Shadow-run new reports and reconciliation against agreed source totals over representative closed and open periods. Every material difference needs a documented explanation.
7. Pilot one tenant with a short posting freeze, final delta migration, physical/financial checks, and smoke tests. Then enable remaining tenants in batches.
8. Rollback before new posting can disable the feature. After new transactions exist, use a tested reverse-migration or forward-fix procedure that preserves invoices, allocations, payments, and audit records; never restore an old database and discard new trading activity.

## 8. Test and release gates

Test mixed-cost lots, one sale consuming multiple layers, fractional units, variant isolation, optional warehouses, purchase discounts/landed costs, invoice discounts and rounding, tax, services, partial payments, advances, partial/repeated/damaged returns, supplier returns, reversals, backdating, period locks, inventory write-downs, reservations, archived accounts/categories, leap/month boundaries, and historical fixed-asset balances.

Integration tests must include two tills competing for the last units, an initially absent balance row, duplicate retries, observer duplication, transaction rollback, cross-tenant access, and migration restart/idempotency. Add report-export parity and all reconciliation equations. Update existing average-based tests only after preserving the intended legacy/cutover contract.

Release requires: no unexplained reconciliation differences; no overselling or cross-tenant leakage; no historical-profit changes from later ordinary receipts; validated restore/rollback procedure; a successfully reconciled pilot; and documented unresolved legacy evidence rather than a false claim of perfect historical reconstruction.

## 9. Implementation order

1. Audit and accounting/pricing policy specification.
2. Ledger foundation, dated opening balances, period locks, exact arithmetic.
3. Tenant policy settings/history, lots, FIFO and moving-average valuation strategies, allocation and inventory posting services.
4. All purchase/sale/payment/return/correction paths and UI.
5. Shared report queries, drilldowns, reconciliation, PDF/Excel parity.
6. Dry-run migration, acceptance tests, shadow reporting, pilot and staged rollout.

Do not ship a report-only formula patch first: it would leave the underlying transactions inconsistent. No unconditional zero-defect guarantee is possible; these release gates make correctness measurable and prevent unexplained differences from being accepted as complete.
