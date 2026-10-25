# 📱 TrustCash: Mobile-First Responsive & Native App Experience Master Plan

A comprehensive, task-based roadmap to transform **TrustCash** into a professional, mobile-first web app (PWA) with native mobile app-like smoothness, touch ergonomics, and 100% reliable modal action buttons.

---

## 🎯 Primary Goals & Guiding Principles
1. **Zero Hidden Save Buttons**: Every Create/Edit modal must have a sticky/pinned action footer (`Save` and `Cancel`) that remains 100% visible and clickable on any mobile screen or with on-screen virtual keyboard open.
2. **Native Bottom Sheet Modals on Mobile**: On mobile screens (`<sm`), modals slide up as bottom sheets (`rounded-t-3xl`, drag handle, sticky header, scrollable body, sticky footer) with iOS/Android safe area support (`env(safe-area-inset-bottom)`).
3. **No iOS Auto-Zoom Glitch**: Prevent iPhone Safari from forcibly zooming in on form inputs by ensuring `text-base sm:text-xs` (16px base font size on mobile).
4. **Touch-Friendly Ergonomics**: Minimum 44px tap targets (`min-h-[44px]`), numeric keypads (`inputmode="decimal"`) for currency/stock, and overscroll containment.
5. **App Cards on Mobile**: Transform wide desktop tables into touch-friendly cards on mobile devices (e.g., Customers, Suppliers, Products, Sales, Expenses).

---

## 📋 Task-Based Execution Progress

### Phase 1: Core Modal Engine & System Components
- [x] **Task 1.1**: Upgrade `Modal.vue` with mobile bottom-sheet architecture, independent scrollable content, drag handle, and dvh support.
- [x] **Task 1.2**: Upgrade `ConfirmationModal.vue` with mobile bottom-sheet styling and sticky action buttons.
- [x] **Task 1.3**: Add global CSS utilities in `resources/css/app.css` for mobile safe-areas, touch manipulation, and custom smooth scrollbars.

---

### Phase 2: Create & Edit Modals Refactoring (Save Button Guarantee)
- [x] **Task 2.1**: Refactor `Brands/Index.vue` Create/Edit modal with sticky header, scrollable body & sticky save button.
- [x] **Task 2.2**: Refactor `Units/Index.vue` Create/Edit modal with sticky header, scrollable body & sticky save button.
- [x] **Task 2.3**: Refactor `ExpenseCategories/Index.vue` Create/Edit modal with sticky save button.
- [x] **Task 2.4**: Refactor `Expenses/Index.vue` Create/Edit modal with sticky header, scrollable body & sticky save button.
- [x] **Task 2.5**: Refactor `Suppliers/Index.vue` modal (migrate custom popup to responsive Modal with sticky save button).
- [x] **Task 2.6**: Refactor `Branches/Index.vue` modal (migrate custom popup to responsive Modal with sticky save button).
- [x] **Task 2.7**: Refactor `Users.vue` modal (migrate custom popup with staff roles/permissions to responsive Modal with sticky save button).
- [x] **Task 2.8**: Refactor `FixedAssets/Index.vue` Asset Head modal & Asset Item modal with sticky save buttons.
- [x] **Task 2.9**: Refactor `FundManagement/Index.vue` Transfer & Bank Account modals.
- [x] **Task 2.10**: Refactor `ExtraIncome/Index.vue` & `ExtraIncomeCategory/Index.vue` modals.
- [x] **Task 2.11**: Refactor `PaymentModal.vue` (POS Checkout Modal) for seamless mobile keypad & payment actions.

---

### Phase 3: Mobile Touch, Inputs & Ergonomics
- [x] **Task 3.1**: Fix iOS auto-zoom across TextInput, SelectInput, PriceInput, TextArea (`text-base sm:text-xs`).
- [x] **Task 3.2**: Add `inputmode="decimal"` and `inputmode="numeric"` to money, quantity, phone, and barcode inputs.
- [x] **Task 3.3**: Ensure all primary interactive buttons have `min-h-[42px]` or `min-h-[44px]` for thumb-friendly tapping.

---

### Phase 4: Mobile Card View Transformations (Tables ➔ App Cards)
- [x] **Task 4.1**: `Customers/Index.vue` — Dual View (Desktop Table + Mobile Cards with Call, Due, and Action shortcuts).
- [x] **Task 4.2**: `Suppliers/Index.vue` — Dual View (Desktop Table + Mobile Cards with Pay Supplier, Phone, and Due indicators).
- [x] **Task 4.3**: `Expenses/Index.vue` — Dual View (Desktop Table + Mobile Cards with Category, Account, Amount).
- [x] **Task 4.4**: `Sales/Index.vue` — Dual View (Desktop Table + Mobile Cards with Invoice #, Customer, Paid/Due Badge, Print).
- [x] **Task 4.5**: `Products/Index.vue` / Partials — Dual View (Desktop Table + Mobile Cards with Thumbnail, Price, Stock Badge).

---

### Phase 5: Layout & Navigation Polish & Verification
- [x] **Task 5.1**: Polish `AdminLayout.vue` mobile top bar and mobile bottom navigation with safe-area insets.
- [x] **Task 5.2**: Test and verify the build with `npm run build` to ensure zero compilation or syntax errors.

---

### Phase 6: Advanced Mobile Native Experience & Remaining Modals
- [x] **Task 6.1**: Add `viewport-fit=cover` and fast tap `touch-action: manipulation` in `resources/views/app.blade.php` and `resources/css/app.css`.
- [x] **Task 6.2**: Refactor `Suppliers/Show.vue` Pay Due Modal to use `<Modal>` with bottom-sheet styling, scrollable body, and sticky confirm button.
- [x] **Task 6.3**: Refactor `ProductStocks/Index.vue` Stock History modal (with responsive mobile card view for transactions) and Delete Confirmation modal.
- [x] **Task 6.4**: Refactor `Sales/Edit.vue` raw confirmation popup into responsive modal.
- [x] **Task 6.5**: Enhance `ReceiptPayment/Index.vue` with mobile collapsible filter bar ("ফিল্টার ফিল্টার করুন / লুকান") to maximize screen view for mobile users.
- [x] **Task 6.6**: Run `npm run build` to verify all updates compile cleanly.

