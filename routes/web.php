<?php

use App\Http\Controllers\Admin\BalanceSheetController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BankReportController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BankTransactionReportController;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerSalesReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExtraIncomeController;
use App\Http\Controllers\Admin\FundManagementController;
use App\Http\Controllers\Admin\IncomeExpenditureController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductAnalysisReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\ProductStockReportController;
use App\Http\Controllers\Admin\ReceiptPaymentController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SaleReportController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\PendingSaleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SaleReturnController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Central\LandingPageController;
use App\Http\Controllers\Central\TenantRegistrationController;
use App\Http\Controllers\Central\SuperAdminController;
use App\Http\Controllers\ExtraIncomeCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\StorefrontController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

use Inertia\Inertia;


// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });


// SaaS Landing Page & Registration
Route::get('/favicon.ico', function () {
    try {
        $favicon = \App\Models\Setting::getCentral('site_favicon', '');
        if (!empty($favicon)) {
            $path = base_path('storage/app/public/' . ltrim(str_replace('/storage/', '', $favicon), '/'));
            if (file_exists($path)) {
                return response()->file($path);
            }
        }
    } catch (\Throwable $e) {}
    if (file_exists(public_path('favicon.png'))) {
        return response()->file(public_path('favicon.png'));
    }
    abort(404);
});

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::get('/register-business', [TenantRegistrationController::class, 'showRegistrationForm'])->name('tenant.register');
Route::post('/register-business', [TenantRegistrationController::class, 'register'])->name('tenant.register.submit');
Route::get('/api/check-subdomain', [TenantRegistrationController::class, 'checkSubdomain'])->name('tenant.check-subdomain');

// Public storefront demo
Route::get('/storefront', [StorefrontController::class, 'index'])->name('storefront');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart');
Route::post('/cart/add', [StorefrontController::class, 'addToCart'])->name('cart.add');
Route::post('/cart/update', [StorefrontController::class, 'updateCart'])->name('cart.update');
Route::post('/cart/remove', [StorefrontController::class, 'removeFromCart'])->name('cart.remove');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout');
Route::post('/checkout/submit', [StorefrontController::class, 'submitOrder'])->name('checkout.submit');
Route::get('/about', fn() => Inertia::render('About'))->name('about');
Route::get('/contact', fn() => Inertia::render('Contact'))->name('contact');
Route::get('/privacy', fn() => Inertia::render('Privacy'))->name('privacy');
Route::get('/terms', fn() => Inertia::render('Terms'))->name('terms');

Route::get('/storage/{path}', function (string $path) {
    // 1. Try active tenant context if initialized
    if (function_exists('tenant') && tenant()) {
        $tenantFile = storage_path("app/public/{$path}");
        if (is_file($tenantFile)) {
            return response()->file($tenantFile);
        }
    }

    // 2. Check host subdomain for tenant storage folder
    $host = request()->getHost();
    $subdomain = explode('.', $host)[0];
    if ($subdomain && is_dir(base_path("storage/tenant{$subdomain}"))) {
        $tenantFile = base_path("storage/tenant{$subdomain}/app/public/{$path}");
        if (is_file($tenantFile)) {
            return response()->file($tenantFile);
        }
    }

    // 3. Fallback to central storage
    $centralFile = base_path("storage/app/public/{$path}");
    if (is_file($centralFile)) {
        return response()->file($centralFile);
    }

    abort(404);
})->where('path', '.*')->name('storage.file');

Route::get('/storage-link', function () {
    Artisan::call('storage:link');

    return response()->json(['message' => 'Storage link created successfully.']);
})->name('storage.link');

// Route for running migrations
Route::get('/migrate', function () {
    Artisan::call('migrate');

    return response()->json(['message' => 'Migrations run successfully.']);
})->name('migrate');

Route::get('/run-backup', function () {
    try {
        // Capture the output
        $output = '';
        $result = Artisan::call('backup:database', [], $output);

        // Get the output buffer
        $output = Artisan::output();

        return response()->json([
            'status' => ($result === 0) ? 'success' : 'error',
            'message' => 'Backup process completed',
            'output' => $output,
            'result_code' => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('admin.dashboard.data');

    Route::resource('categories', CategoryController::class);
    Route::resource('units', UnitController::class);
    Route::resource('brands', BrandController::class);

    Route::get('products/download-pdf', [ProductController::class, 'downloadPdf'])
        ->name('products.download-pdf');
    // Your existing routes
    Route::resource('products', ProductController::class);
    Route::post('products/optimize-images', [ProductController::class, 'optimizeImages'])
        ->name('products.optimize-images');

    Route::resource('product-stocks', ProductStockController::class)
        ->only(['index', 'create', 'store', 'destroy']);
    Route::get('product-stocks/history/{productId}', [ProductStockController::class, 'getStockHistory'])
        ->name('product-stocks.history');

    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])
        ->name('customers.toggle-status');
    Route::post('customers/{customer}/add-payment', [CustomerController::class, 'addPayment'])
        ->name('customers.add-payment');

    // Supplier Management
    Route::resource('suppliers', SupplierController::class);
    Route::post('suppliers/{supplier}/add-payment', [SupplierController::class, 'addPayment'])
        ->name('suppliers.add-payment');
    Route::post('suppliers/{supplier}/pay-due', [SupplierController::class, 'addPayment'])
        ->name('suppliers.pay-due');

    // Purchase Management
    Route::resource('purchases', PurchaseController::class);

    // Sales Returns
    Route::get('returns/search-sale', [SaleReturnController::class, 'searchSale'])->name('returns.search-sale');
    Route::resource('returns', SaleReturnController::class);

    // Branches
    Route::resource('branches', BranchController::class);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::resource('bank-accounts', BankAccountController::class);
    Route::resource('bank-transactions', BankTransactionController::class);
    // Route::resource('sales', SaleController::class);

    Route::get('reports/bank-transactions/pdf', [BankTransactionReportController::class, 'downloadPdf'])
        ->name('reports.bank-transactions.pdf');

    Route::resource('extra-incomes', ExtraIncomeController::class);
    Route::resource('extra-income-categories', ExtraIncomeCategoryController::class);
    Route::resource('funds', FundManagementController::class);


    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/search-products', [PosController::class, 'searchProducts'])->name('pos.search-products');
    Route::post('/pos/store', [PosController::class, 'store'])->name('pos.store');
    Route::get('pos/search-by-barcode', [PosController::class, 'searchByBarcode'])
        ->name('pos.search-by-barcode');

    Route::get('/pos/print-receipt/{id}', [PosController::class, 'printReceipt'])
        ->name('pos.print-receipt');
    Route::get('/pos/products-by-category', [PosController::class, 'productsByCategory'])
        ->name('pos.products.by.category');
    Route::get('/pos/products', [PosController::class, 'products'])
        ->name('pos.products');

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/{id}', [SaleController::class, 'show'])->name('show');
        Route::get('/{sale}/edit', [SaleController::class, 'edit'])->name('edit');
        Route::put('/{sale}', [SaleController::class, 'update'])->name('update');
        Route::delete('/{sale}', [SaleController::class, 'destroy'])->name('destroy');
        Route::get('/print/{id}', [SaleController::class, 'printReceipt'])->name('print-receipt');
    });
    Route::post('/products/{product}/barcode', [BarcodeController::class, 'generate'])->name('products.barcode.generate');
    Route::match(['get', 'post'], '/products/barcode/print', [BarcodeController::class, 'print'])->name('products.barcode.print');

    // Pending public orders approval
    Route::get('/pending-sales', [PendingSaleController::class, 'index'])->name('pending-sales.index');
    Route::post('/pending-sales/{pendingSale}/approve', [PendingSaleController::class, 'approve'])->name('pending-sales.approve');
    Route::post('/pending-sales/{pendingSale}/reject', [PendingSaleController::class, 'reject'])->name('pending-sales.reject');
    Route::post('/pending-sales/{pendingSale}/items/add', [PendingSaleController::class, 'addItem'])->name('pending-sales.items.add');
    Route::post('/pending-sales/{pendingSale}/items/{item}/update', [PendingSaleController::class, 'updateItem'])->name('pending-sales.items.update');
    Route::post('/pending-sales/{pendingSale}/items/{item}/remove', [PendingSaleController::class, 'removeItem'])->name('pending-sales.items.remove');


    Route::get('/reports/bank', [BankReportController::class, 'index'])->name('reports.bank');
    Route::get('/reports/bank/download', [BankReportController::class, 'downloadPdf'])
        ->name('reports.bank.download');

    Route::get('/reports/bank-transaction-report', [BankTransactionReportController::class, 'index'])
        ->name('bank-transaction-report');

    Route::get('/reports/stock', [ProductStockReportController::class, 'index'])
        ->name('reports.stock');
    Route::get('/reports/stock/download', [ProductStockReportController::class, 'downloadPdf'])
        ->name('reports.stock.download');

    Route::get('/reports/sales', [SaleReportController::class, 'index'])
        ->name('reports.sales');
    Route::get('/reports/sales/download', [SaleReportController::class, 'downloadPdf'])
        ->name('reports.sales.download');

    Route::get('/reports/customer-sales', [CustomerSalesReportController::class, 'index'])
        ->name('reports.customer-sales.index');

    Route::get('/reports/income-expenditure', [IncomeExpenditureController::class, 'index'])
        ->name('reports.income-expenditure');
    Route::get('/reports/income-expenditure/pdf', [IncomeExpenditureController::class, 'downloadPdf'])
        ->name('reports.income-expenditure.pdf');
    Route::get('/reports/income-expenditure/download', [BalanceSheetController::class, 'downloadPdf'])
        ->name('reports.income-expenditure.download');

    Route::get('/reports/balance-sheet', [BalanceSheetController::class, 'index'])
        ->name('reports.balance-sheet');

    Route::get('/reports/balance-sheet/pdf', [BalanceSheetController::class, 'downloadPdf'])
        ->name('reports.balance-sheet.pdf');
    Route::get('/reports/balance-sheet/download', [BalanceSheetController::class, 'downloadPdf'])
        ->name('reports.balance-sheet.download');


    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/restore', [ExpenseController::class, 'restore'])->name('expenses.restore');
    Route::resource('expense-categories', ExpenseCategoryController::class);

    // Fixed Asset Management
    Route::resource('fixed-assets', \App\Http\Controllers\Admin\FixedAssetController::class);
    Route::post('fixed-assets/{fixedAsset}/items', [\App\Http\Controllers\Admin\FixedAssetController::class, 'storeItem'])->name('fixed-assets.items.store');
    Route::put('fixed-assets/items/{item}', [\App\Http\Controllers\Admin\FixedAssetController::class, 'updateItem'])->name('fixed-assets.items.update');
    Route::delete('fixed-assets/items/{item}', [\App\Http\Controllers\Admin\FixedAssetController::class, 'destroyItem'])->name('fixed-assets.items.destroy');
    Route::post('fixed-assets/{fixedAsset}/restore', [\App\Http\Controllers\Admin\FixedAssetController::class, 'restore'])->name('fixed-assets.restore');

    Route::controller(ProductAnalysisReportController::class)->group(function () {
        Route::get('/reports/product-analysis', 'index')->name('reports.product-analysis');
        Route::get('/reports/product-analysis/data', 'getAnalysisData')->name('reports.product-analysis.data');
    });
    Route::get('/reports/product-analysis/pdf', [ProductAnalysisReportController::class, 'downloadPdf'])
        ->name('reports.product-analysis.pdf');

    Route::get('/reports/receipt-payment', [ReceiptPaymentController::class, 'index'])
        ->name('reports.receipt-payment');
    Route::get('/reports/receipt-payment/pdf', [ReceiptPaymentController::class, 'downloadPdf'])
        ->name('reports.receipt-payment.pdf');

});

Route::get('products/search', [ProductController::class, 'search'])->name('api.products.search');

Route::get('/dashboard', function () {
    $roleSlug = auth()->user()?->role?->slug ?? '';
    if (in_array($roleSlug, ['super-admin', 'superadmin'])) {
        return redirect()->route('super-admin.dashboard');
    }
    return redirect('/admin/dashboard');
})->name('dashboard');

// NOTE: Home route is the public storefront above.

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

// Central SaaS Landlord / Super Admin Panel
Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    // Tenants Management
    Route::get('/tenants', [SuperAdminController::class, 'tenants'])->name('tenants.index');
    Route::post('/tenants', [SuperAdminController::class, 'createTenant'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [SuperAdminController::class, 'showTenant'])->name('tenants.show');
    Route::post('/tenants/{tenant}/approve', [SuperAdminController::class, 'approveTenant'])->name('tenants.approve');
    Route::post('/tenants/{tenant}/toggle-status', [SuperAdminController::class, 'toggleTenantStatus'])->name('tenants.toggle-status');
    Route::post('/tenants/{tenant}/extend-trial', [SuperAdminController::class, 'extendTrial'])->name('tenants.extend-trial');
    Route::post('/tenants/{tenant}/update-subscription', [SuperAdminController::class, 'updateSubscription'])->name('tenants.update-subscription');
    Route::post('/tenants/{tenant}/reset-password', [SuperAdminController::class, 'resetPassword'])->name('tenants.reset-password');
    Route::get('/tenants/{tenant}/impersonate', [SuperAdminController::class, 'impersonate'])->name('tenants.impersonate');
    Route::delete('/tenants/{tenant}', [SuperAdminController::class, 'deleteTenant'])->name('tenants.destroy');

    // Plans Management
    Route::get('/plans', [SuperAdminController::class, 'plans'])->name('plans.index');
    Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('plans.destroy');

    // Billing & Payment Approvals
    Route::get('/payments', [SuperAdminController::class, 'payments'])->name('payments.index');
    Route::post('/payments/{payment}/approve', [SuperAdminController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [SuperAdminController::class, 'rejectPayment'])->name('payments.reject');

    // Global Platform Settings
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [SuperAdminController::class, 'updateSettings'])->name('settings.update');
});

// Central Subscription Payments
Route::post('/payment/subscription/initiate', [App\Http\Controllers\Central\SubscriptionPaymentController::class, 'initiate'])->name('payment.subscription.initiate');
Route::post('/payment/sslcommerz/success', [App\Http\Controllers\Central\SubscriptionPaymentController::class, 'success'])->name('payment.sslcommerz.success');
Route::post('/payment/sslcommerz/fail', [App\Http\Controllers\Central\SubscriptionPaymentController::class, 'fail'])->name('payment.sslcommerz.fail');
Route::post('/payment/sslcommerz/cancel', [App\Http\Controllers\Central\SubscriptionPaymentController::class, 'cancel'])->name('payment.sslcommerz.cancel');
Route::post('/payment/sslcommerz/ipn', [App\Http\Controllers\Central\SubscriptionPaymentController::class, 'success'])->name('payment.sslcommerz.ipn');

require __DIR__ . '/auth.php';
