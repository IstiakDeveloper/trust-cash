<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleCreator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use NumberFormatter;

class PosController extends Controller
{
    private ?bool $fifoCosting = null;
    public function index()
    {
        return Inertia::render('Admin/Pos/Index', [
            'customers' => Customer::active()
                ->select('id', 'name', 'phone', 'balance', 'credit_limit')
                ->get(),
            'bankAccounts' => BankAccount::active()
                ->select('id', 'account_name', 'bank_name', 'current_balance')
                ->get(),
            'categories' => Category::where('status', true)
                ->select('id', 'name')
                ->get(),
        ]);
    }

    public function products()
    {
        return Product::with($this->posProductRelations())
            ->where('status', true)
            ->get()
            ->map(fn ($product) => $this->posProduct($product));
    }

    public function productsByCategory(Request $request)
    {
        $query = Product::with($this->posProductRelations())->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return $query->get()->map(fn ($product) => $this->posProduct($product));
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with($this->posProductRelations())->where('status', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        return $query->take(10)->get()->map(fn ($product) => $this->posProduct($product));
    }

    public function searchByBarcode(Request $request)
    {
        $barcode = $request->input('barcode');
        $product = Product::with(['productStocks', 'primaryImage'])
            ->where('status', true)
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)->orWhere('sku', $barcode);
            })
            ->first();

        if (! $product) {
            return response()->json([]);
        }

        $product->loadMissing($this->posProductRelations());

        return response()->json([$this->posProduct($product)]);
    }

    private function usesFifo(): bool
    {
        return $this->fifoCosting ??= DB::table('inventory_policies')->where('id', 1)->value('method') === 'fifo';
    }

    private function posProductRelations(): array
    {
        return [
            'productStocks',
            'primaryImage',
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
        ];
    }

    private function posProduct(Product $product): array
    {
        $img = $product->primaryImage ?: $product->images->first();
        $fifo = $this->usesFifo();
        $lots = $fifo && ! $product->isService() ? $product->sellableLots() : collect();
        $next = $lots->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'type' => $product->type ?? 'product',
            'is_service' => $product->isService(),
            'sku' => $product->sku,
            'selling_price' => ($fifo && $next) ? $next['selling_price'] : $product->selling_price,
            'lots' => $lots->values(),
            'stock' => $product->isService() ? 999999 : $product->availableQuantity(),
            'image' => $img ? $img->image : null,
            'image_url' => $img ? ('/storage/'.ltrim($img->image, '/')) : null,
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id', 'items.*.lot_id' => 'nullable|exists:product_stocks,id',
            'items.*.quantity' => 'required|numeric|min:0.000001', 'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0', 'discount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0', 'paid' => 'required|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id', 'customer_id' => 'nullable|exists:customers,id',
            'note' => 'nullable|string|max:1000', 'idempotency_key' => 'nullable|string|max:100',
        ]);
        try {
            $sale = app(SaleCreator::class)->create(
                $data['items'], (float) $data['subtotal'], (float) $data['discount'], (float) $data['total'],
                min((float) $data['paid'], (float) $data['total']), $data['bank_account_id'] ?? null,
                $data['customer_id'] ?? null, $data['note'] ?? null, Auth::id(), requestKey: $data['idempotency_key'] ?? null
            );
            if ($request->wantsJson() || $request->boolean('is_offline_sync')) {
                return response()->json(['success' => true, 'message' => 'Sale processed successfully', 'sale' => $sale]);
            }

            return to_route('admin.pos.index')->with('sale', $sale);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->boolean('is_offline_sync')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function printReceipt($id)
    {
        // Fetch the sale with related data, including product categories
        $sale = Sale::with([
            'saleItems.product.category', // Include product category relationship
            'customer',
            'createdBy',
        ])->findOrFail($id);

        // Group items by category
        $itemsByCategory = $sale->saleItems->groupBy(function ($item) {
            return $item->product->category->name ?? 'Uncategorized';
        });

        // Calculate category subtotals
        $categoryTotals = [];
        foreach ($itemsByCategory as $category => $items) {
            $categoryTotals[$category] = $items->sum('subtotal');
        }

        $f = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        $amountInWords = ucfirst($f->format((float) $sale->total)).' taka only';

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'itemsByCategory' => $itemsByCategory, // Pass the grouped items
            'categoryTotals' => $categoryTotals,
            'amountInWords' => $amountInWords,
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address'),
                'phone' => config('app.phone'),
                'email' => config('app.email'),
            ],
        ]);

        return $pdf->stream("receipt-{$sale->invoice_no}.pdf");
    }

    protected function generateUniqueInvoiceNumber($date)
    {
        // Find highest sequence number for today including soft-deleted rows
        $lastSale = Sale::withTrashed()
            ->where('invoice_no', 'like', "INV-{$date}-%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastSale && preg_match('/INV-'.$date.'-(\d+)/', $lastSale->invoice_no, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        // Auto-skip any existing or soft-deleted invoice numbers
        while (Sale::withTrashed()->where('invoice_no', 'INV-'.$date.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT))->exists()) {
            $nextSequence++;
        }

        return 'INV-'.$date.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
