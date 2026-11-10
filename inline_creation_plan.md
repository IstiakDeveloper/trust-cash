# Inline creation: plan and implementation

## Flow

- Product Create/Edit: searchable Category, Brand and Unit selectors include “Create new”. The search text prefills the creation modal.
- Purchase: existing name/SKU/barcode search remains. “New product” opens an inline product modal with the search text prefilled; saving immediately adds the product as a purchase line.
- Stock: product selector includes inline creation; saving selects the new product and fills its selling price.
- Product creation inside Purchase/Stock supports inline Category, Brand and Unit creation as well. Newly created units are immediately available on purchase lines.

## Data and safety

Use existing authenticated, tenant-scoped create routes and validation. Requests accepting JSON receive the created record with HTTP 201; ordinary page submissions retain redirects. Validation errors return HTTP 422 and stay in the modal. Busy buttons prevent simultaneous submissions from the same modal. Product SKU is generated once per modal draft and remains editable; database uniqueness rejects duplicate SKU/barcode submissions.

Creating a product does not post stock, purchases, cash or accounting entries. The parent form remains in memory throughout creation and cancellation; successfully created lookup records remain saved even when the parent form is cancelled. Cancelling an unsaved modal discards only that modal draft.

Category/Brand support Bangla names through nonempty slug fallbacks. Slug generation considers archived categories. No new migrations or dependencies are required.

## Confirmed scope

The user confirmed that “branch” meant Brand / ব্র্যান্ড. Category, Brand and Unit inline creation is implemented on Product Create/Edit and inside the Purchase/Stock product modal. No clarification remains pending.

## Validation

- QuickCreateTest: JSON creation, Bangla names, lookup loading, product creation without stock/ledger entries, duplicate SKU/category/brand validation, missing fields and unauthenticated requests.
- InventoryAccountingTest + ProductAnalysisTest + QuickCreateTest: 34 passed, 263 assertions.
- Production Vite build passed.
- Interactive browser testing has not been performed in this environment; staging acceptance should cover nested modal Save/Cancel/Escape, keyboard selection, mobile sizing, parent draft preservation, new-unit selection and duplicate validation.
