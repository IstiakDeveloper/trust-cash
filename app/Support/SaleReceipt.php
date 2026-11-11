<?php

namespace App\Support;

use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use NumberFormatter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SaleReceipt
{
    public static function response(int $id): HttpResponse|Response
    {
        $sale = Sale::with([
            'saleItems.product',
            'customer',
            'createdBy',
            'bankAccount',
        ])->findOrFail($id);

        $size = Setting::get('receipt_paper_size', '80mm');
        if (! in_array($size, ['80mm', '58mm', 'A4'], true)) {
            $size = '80mm';
        }

        if ($size === 'A4') {
            return self::a4($sale);
        }

        $widthMm = $size === '58mm' ? 58 : 80;

        return response()->view('pdf.receipt-thermal', [
            'sale' => $sale,
            'widthMm' => $widthMm,
            'footer' => Setting::get('invoice_footer_note', 'Thank you. Please come again.'),
        ]);
    }

    private static function a4(Sale $sale): Response
    {
        $sale->loadMissing('saleItems.product.category');
        $itemsByCategory = $sale->saleItems->groupBy(fn ($item) => $item->product->category->name ?? 'Uncategorized');
        $categoryTotals = [];
        foreach ($itemsByCategory as $category => $items) {
            $categoryTotals[$category] = $items->sum('subtotal');
        }
        $words = class_exists(NumberFormatter::class)
            ? ucfirst((new NumberFormatter('en', NumberFormatter::SPELLOUT))->format((float) $sale->total)).' taka only'
            : '';

        return Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'itemsByCategory' => $itemsByCategory,
            'categoryTotals' => $categoryTotals,
            'amountInWords' => $words,
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address'),
                'phone' => config('app.phone'),
                'email' => config('app.email'),
            ],
        ])->setPaper('a4', 'portrait')->stream("receipt-{$sale->invoice_no}.pdf");
    }
}
