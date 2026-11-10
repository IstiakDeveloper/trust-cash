<?php

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\FixedAssetItem;
use App\Models\Fund;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Observers\AccountingObserver;
use App\Observers\BankTransactionObserver;
use App\Services\FinancialLedger;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(FinancialLedger::class);
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        BankTransaction::observe(BankTransactionObserver::class);
        foreach ([
            BankAccount::class, BankTransaction::class,
            Sale::class, SalePayment::class, Purchase::class,
            Supplier::class, SupplierPayment::class,
            Expense::class, ExtraIncome::class,
            Fund::class, FixedAssetItem::class,
        ] as $model) {
            $model::observe(AccountingObserver::class);
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
