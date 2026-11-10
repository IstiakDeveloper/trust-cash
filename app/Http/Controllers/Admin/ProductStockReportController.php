<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StockReportExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductStockReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $query = Product::with(['category', 'unit'])
            ->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 'service');
            })
            ->when($request->product_id, function ($q) use ($request) {
                return $q->where('id', $request->product_id);
            })
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            });

        $products = $query->get();

        $reportData = $this->generateReports($products, $fromDate, $toDate);

        return Inertia::render('Admin/Reports/StockReport', [
            'products' => Product::where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 'service');
            })->select('id', 'name', 'sku')->get(),
            'categories' => Category::select('id', 'name')->get(),
            'filters' => [
                'product_id' => $request->product_id,
                'category_id' => $request->category_id,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ],
            'reports' => $reportData['data'],
            'summary' => $reportData['summary'],
        ]);
    }

    private function generateReports($products, $fromDate, $toDate)
    {
        $analysis = Product::getProductAnalysis($fromDate, $toDate)['products']->keyBy('product_id');
        $from = $fromDate->toDateString();
        $through = $toDate->toDateString();
        $reports = $products->filter(fn ($p) => ! $p->isService())->map(function ($product) use ($analysis, $from, $through) {
            $metrics = $analysis->get($product->id);
            if (! $metrics) {
                return null;
            }
            $purchases = ProductStock::where('product_id', $product->id)->whereIn('type', ['purchase', 'purchase_reversal'])
                ->whereBetween('date', [$from, $through])->orderBy('date')->orderBy('id')->get()->map(fn ($stock) => [
                    'date' => $stock->date, 'quantity' => $stock->quantity, 'unit_cost' => $stock->unit_cost,
                    'total_cost' => $stock->quantity > 0 ? $stock->total_cost : bcmul((string) $stock->total_cost, '-1', 6),
                    'available_quantity' => $stock->available_quantity, 'note' => $stock->note, 'lot_number' => $stock->lot_number,
                ]);
            $sales = DB::table('sale_accounting_entries')->where('product_id', $product->id)->whereBetween('date', [$from, $through])
                ->orderBy('date')->orderBy('id')->get()->map(fn ($entry) => [
                    'date' => $entry->date, 'quantity' => $entry->quantity,
                    'invoice_no' => (Sale::withTrashed()->find($entry->sale_id)?->invoice_no ?? 'Archived').($entry->source_type === 'sale' ? '' : ' ('.$entry->source_type.')'),
                    'unit_price' => bccomp((string) $entry->quantity, '0', 6) === 0 ? 0 : bcdiv((string) $entry->net, (string) $entry->quantity, 6),
                    'total' => $entry->net, 'available_quantity' => null,
                ]);

            return [
                'product' => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
                    'category' => $product->category?->name ?? 'Uncategorized', 'unit' => $product->unit?->name ?? ''],
                'summary' => [
                    'opening_stock' => $metrics['before_quantity'], 'current_stock' => $metrics['available_quantity'],
                    'total_purchased' => $metrics['buy_quantity'], 'total_sold' => $metrics['sale_quantity'],
                    'avg_cost' => bccomp($metrics['available_quantity'], '0', 6) > 0 ? bcdiv($metrics['available_stock_value'], $metrics['available_quantity'], 6) : 0,
                    'stock_value' => $metrics['available_stock_value'], 'sales_value' => $metrics['sale_after_discount'], 'purchase_value' => $metrics['total_buy_price'],
                ], 'purchases' => $purchases, 'sales' => $sales,
            ];
        })->filter()->values();

        return ['data' => $reports, 'summary' => [
            'total_purchase_quantity' => $reports->sum('summary.total_purchased'), 'total_purchase_value' => $reports->sum('summary.purchase_value'),
            'total_sales_quantity' => $reports->sum('summary.total_sold'), 'total_sales_value' => $reports->sum('summary.sales_value'),
            'total_current_stock' => $reports->sum('summary.current_stock'), 'total_stock_value' => $reports->sum('summary.stock_value'),
        ]];
    }

    public function downloadPdf(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        $isBn = ($locale === 'bn');
        if ($isBn) {
            app()->setLocale('bn');
        }

        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $query = Product::with(['category', 'unit']);
        if ($request->product_id) {
            $query->where('id', $request->product_id);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();
        $reportData = $this->generateReports($products, $fromDate, $toDate); // Using the updated method

        $data = [
            'reports' => $reportData['data'],
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address', 'Company Address'),
                'phone' => config('app.phone', 'Company Phone'),
                'email' => config('app.email', 'Company Email'),
            ],
            'filters' => [
                'from_date' => $isBn ? to_bangla_date($fromDate->format('Y-m-d')) : $fromDate->format('d M, Y'),
                'to_date' => $isBn ? to_bangla_date($toDate->format('Y-m-d')) : $toDate->format('d M, Y'),
            ],
            'isBn' => $isBn,
            'locale' => $locale,
        ];

        $pdf = Pdf::loadView('reports.stock-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('stock-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now()->endOfMonth();

        $query = Product::with(['category', 'unit']);
        if ($request->product_id) {
            $query->where('id', $request->product_id);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();
        $reportData = $this->generateReports($products, $fromDate, $toDate);

        $fileName = 'stock-report-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new StockReportExport($reportData['data'], $reportData['summary']),
            $fileName
        );
    }
}
