# 🌐 TrustCash: Master Bilingual (বাংলা ও English) & Unified Design Plan

A complete, systematic blueprint to make **every single page** in TrustCash bilingual (Bangla & English toggleable with zero page reload) while adhering to our sleek, modern, uncluttered Admin Theme.

---

## 🏗️ Core Architecture: How It Works Seamlessly

### 1. Global Reactive Translation Engine (`useLanguage`)
We already built `useLanguage.js` with reactive `currentLang` and `t(bnText, enText)`.
- **Zero Page Reload:** Toggling `বাংলা | EN` in the header instantly updates all titles, table headers, buttons, form labels, and status badges.
- **Persistent:** Stores user choice in `localStorage('app_locale')`.
- **Currency & Number Formatting:** Helper to display Bengali numerals (১, ২, ৩...) or English numerals (1, 2, 3...) and BDT `৳` symbol.

### 2. Unified Design Standard (Consistent Admin Theme)
Every page will follow the exact same visual anatomy:
1. **Standard Page Header:**
   - Left: Page Title (bold, text-xl) + Subtitle (text-xs, muted)
   - Right: Primary Action Button (e.g. `+ নতুন বিক্রি`, `+ নতুন পণ্য`, `+ খরচ এন্ট্রি`) + Export/Filter buttons.
2. **Top Metric / KPI Cards (where applicable):**
   - 4 sleek cards with soft indigo/emerald/amber/rose accent, bold numbers, and subtle trend badges.
3. **Filter & Search Bar:**
   - Search input (`পণ্য বা কাস্টমার খুঁজুন...`), Date Range filter, Category dropdown, and Status filter.
4. **Unified Data Table:**
   - Rounded 2xl border, crisp slate header, hover rows, status badges (e.g. `পরিশোধিত` / `Paid`, `বাকি` / `Due`), and action icons (`ভিউ`, `এডিট`, `ডিলিট`).
5. **Standard Slide-over / Modal:**
   - Consistent backdrop blur, rounded-2xl container, clear input labels, and validation feedback.

---

## 📋 Module-by-Module Execution Roadmap

### Phase 1: Core Dashboard & POS (সবচেয়ে বেশি ব্যবহৃত পেজ)
- [ ] **1. Dashboard (`Admin/Dashboard/Index.vue` or `Dashboard.vue`)**
  - KPI Cards: আজকের বিক্রি, নগদ কালেকশন, বাকি পাওনা, আজকের খরচ
  - Graphs & Charts: বিক্রয় গ্রাফ ও মাসিক তুলনামূলক চিত্র
  - Recent Invoices & Low Stock Alert tables
- [ ] **2. POS Terminal (`Admin/Pos/Index.vue` & POS components)**
  - Product Search, Category Pills, Cart calculations, Discount, Tax, Cash notes (`৳১০০`, `৳৫০০`, `৳১০০০`)
  - Payment Modal (ক্যাশ, বিকাশ, নগদ, বাকি)
  - Receipt / Thermal Print slip in Bengali/English

### Phase 2: বেচাকেনা ও অর্ডার (Sales, Invoices, Orders & Returns)
- [ ] **3. Sales List (`Admin/Sales/Index.vue`)**
  - মেমো নম্বর, কাস্টমার নাম, মোট টাকা, ডিসকাউন্ট, পেইড, বাকি, অ্যাকশন
- [ ] **4. Sale Details & Print (`Admin/Sales/Show.vue` & Print)**
  - মেমো ভিউয়ার, ইনভয়েস প্রিন্ট
- [ ] **5. Online / Pending Orders (`Admin/PendingSales/Index.vue`)**
  - অর্ডার রিকোয়েস্ট, আইটেম এডিট, অ্যাপ্রুভ / ক্যানসেল
- [ ] **6. Sale Returns (`Admin/Returns/Index.vue` & `Create.vue`)**
  - রিটার্ন মেমো অনুসন্ধান, ফেরত আইটেম ও টাকা ফেরত

### Phase 3: পণ্য ও স্টক ম্যানেজমেন্ট (Products & Inventory)
- [ ] **7. Product List (`Admin/Products/Index.vue`)**
  - ছবির থাম্বনেইল, পণ্যের নাম, কোড/বারকোড, ক্রয়মূল্য, বিক্রয়মূল্য, বর্তমান স্টক, অ্যাকশন
- [ ] **8. Create & Edit Product (`Admin/Products/Create.vue`, `Edit.vue`)**
  - সাধারণ তথ্য, ক্যাটাগরি, ব্র্যান্ড, ইউনিট, প্রাইসিং, ওপেনিং স্টক, ভ্যারিয়েন্ট
- [ ] **9. Stock History & Movement (`Admin/ProductStocks/Index.vue`)**
  - স্টক হিস্ট্রি, স্টক ইন/আউট, ড্যামেজ অ্যাডজাস্টমেন্ট
- [ ] **10. Categories, Brands & Units**
  - `Admin/Categories/Index.vue`
  - `Admin/Brands/Index.vue`
  - `Admin/Units/Index.vue`

### Phase 4: পার্টি ও বাকি খাতা (Customers & Suppliers)
- [ ] **11. Customers & Due Khata (`Admin/Customers/Index.vue`, `Show.vue`)**
  - কাস্টমার তালিকা, মোবাইল নম্বর, মোট ক্রয়, মোট বাকি, পেমেন্ট সংগ্রহ (Collect Due)
- [ ] **12. Suppliers / মহাজন (`Admin/Suppliers/Index.vue`, `Show.vue`)**
  - সাপ্লায়ার তালিকা, কোম্পানির নাম, দেনা টাকা, পেমেন্ট প্রদান (Pay Supplier)

### Phase 5: ক্রয় ও সংগ্রহ (Purchases & Procurement)
- [ ] **13. Purchase List (`Admin/Purchases/Index.vue`)**
  - পারচেজ ইনভয়েস, সাপ্লায়ার নাম, চালানের তারিখ, মোট টাকা, পেইড, বাকি
- [ ] **14. Create Purchase (`Admin/Purchases/Create.vue`)**
  - পণ্য নির্বাচন, ক্রয়মূল্য, পরিমাণ, পেমেন্ট মেথড

### Phase 6: ক্যাশ ও হিসাব-নিকাশ (Finance, Banking & Expenses)
- [ ] **15. Store Expenses (`Admin/Expenses/Index.vue`)**
  - খরচের তালিকা, ভাউচার তারিখ, খাত, পরিমাণ, ব্যাংক/ক্যাশ একাউন্ট
- [ ] **16. Expense Categories (`Admin/ExpenseCategories/Index.vue`)**
- [ ] **17. Bank & Cash Accounts (`Admin/BankAccounts/Index.vue`)**
  - নগদ ক্যাশ, ব্যাংক ব্যালেন্স, মোবাইল ব্যাংকিং (bKash/Nagad)
- [ ] **18. Fund Transfers (`Admin/FundManagement/Index.vue`)**
  - ক্যাশ থেকে ব্যাংক বা ব্যাংক থেকে ক্যাশে ট্রান্সফার
- [ ] **19. Extra Income & Categories (`Admin/ExtraIncome/Index.vue`)**

### Phase 7: সকল রিপোর্ট ও হিসাব বিবরণী (Reports & Analytics)
- [ ] **20. Sales Report (`Admin/Reports/SalesReport.vue`)**
  - তারিখ অনুযায়ী মোট বিক্রি, নগদ, বাকি, লাভ
- [ ] **21. Stock Report (`Admin/Reports/StockReport.vue`)**
  - বর্তমান স্টক ভ্যালু, স্টক শেষ হওয়া আইটেম
- [ ] **22. Income & Expenditure / লাভ-ক্ষতি (`Admin/Reports/IncomeExpenditure.vue`)**
  - মোট রেভিনিউ, পণ্যের ক্রয়মূল্য (COGS), মোট লাভ, পরিচালন ব্যয়, নিট লাভ
- [ ] **23. Balance Sheet (`Admin/Reports/BalanceSheet.vue`)**
  - অ্যাসেট (নগদ, স্টক, কাস্টমার বাকি) এবং লায়াবিলিটি (সাপ্লায়ার বাকি)
- [ ] **24. Product Analysis Report (`Admin/Reports/ProductAnalysis.vue`)**
  - কোন পণ্য সবচেয়ে বেশি বিক্রি হয়েছে (Top Selling Products)
- [ ] **25. Bank Transaction Report & Receipt Payment**
  - `Admin/Reports/BankReport.vue`, `ReceiptPayment/Index.vue`

### Phase 8: ব্রাঞ্চ, স্টাফ ও সেটিংস (Branches, Staff & Settings)
- [ ] **26. Business Settings (`Admin/Settings/Settings.vue`)**
  - দোকানের নাম, লোগো, ফোন, ঠিকানা, ইনভয়েস প্রিন্ট হেডার/ফুটার
- [ ] **27. Branches (`Admin/Branches/Index.vue`)**
  - শাখা তৈরি ও ম্যানেজার অ্যাসাইন
- [ ] **28. Staff & Users (`Admin/Users.vue`)**
  - ক্যাশিয়ার, ম্যানেজার তৈরি ও পারমিশন

---

## 🎨 Reusable Common Translation Dictionary (`resources/js/utils/translations.js`)
একটি সেন্ট্রাল ফাইল তৈরি করব যাতে সব পেজ একই শব্দগুলো ব্যবহার করে:
- `common.save` ➔ "সংরক্ষণ করুন" / "Save"
- `common.cancel` ➔ "বাতিল" / "Cancel"
- `common.delete` ➔ "মুছুন" / "Delete"
- `common.search` ➔ "খুঁজুন..." / "Search..."
- `common.status` ➔ "স্ট্যাটাস" / "Status"
- `common.action` ➔ "অ্যাকশন" / "Action"
- `common.due` ➔ "বাকি" / "Due"
- `common.paid` ➔ "পরিশোধিত" / "Paid"
- `common.total` ➔ "মোট" / "Total"
- `common.date` ➔ "তারিখ" / "Date"

---

## 🚀 Execution Strategy:
আমরা ক্রমান্বয়ে ফেজ ধরে ধরে প্রতিটি পেজকে রূপান্তর করব এবং প্রতি ফেজ শেষে বিল্ড টেস্ট করব যাতে কোড সব সময় প্রোডাকশন-রেডি থাকে।
