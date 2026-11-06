<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeController extends Controller
{
    public function generate(Product $product)
    {
        $pngGenerator = new BarcodeGeneratorPNG;
        $svgGenerator = new BarcodeGeneratorSVG;

        if (! $product->barcode) {
            $product->barcode = $this->generateUniqueBarcode();
            $product->save();
        }

        try {
            $svgBarcode = $svgGenerator->getBarcode(
                $product->barcode,
                $svgGenerator::TYPE_CODE_128,
                1.5,
                35
            );
            $svgDataUri = 'data:image/svg+xml;base64,'.base64_encode($svgBarcode);
        } catch (\Throwable $e) {
            $svgDataUri = null;
        }

        // Generate and store PNG
        $barcode = $pngGenerator->getBarcode(
            $product->barcode,
            $pngGenerator::TYPE_CODE_128,
            2,
            40,
            [0, 0, 0]
        );

        $path = 'barcodes/'.$product->barcode.'.png';
        Storage::disk('public')->makeDirectory('barcodes');
        Storage::disk('public')->put($path, $barcode);

        return response()->json([
            'barcode' => $product->barcode,
            'image_url' => $svgDataUri ?: Storage::disk('public')->url($path),
            'png_url' => Storage::disk('public')->url($path),
            'product' => $product->only(['id', 'name', 'sku', 'selling_price']),
        ]);
    }

    private function generateUniqueBarcode()
    {
        do {
            // Generate a 6-digit number
            $barcode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    private function calculateEAN13CheckDigit($barcode)
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($barcode[$i]) * ($i % 2 ? 3 : 1);
        }

        return (10 - ($sum % 10)) % 10;
    }

    public function print(Request $request)
    {
        // Handle GET requests with missing products gracefully
        if ($request->isMethod('get') && ! $request->has('products') && ! $request->has('product_id')) {
            return redirect()->route('admin.products.index')
                ->with('warning', 'বারকোড প্রিন্ট করার জন্য অন্তত একটি পণ্য নির্বাচন করুন / Please select at least one product to print barcodes.');
        }

        // Handle single product query parameter via GET
        if ($request->isMethod('get') && $request->has('product_id')) {
            $request->merge([
                'products' => [
                    [
                        'id' => (int) $request->product_id,
                        'copies' => (int) ($request->copies ?? 1),
                    ],
                ],
                'paperSize' => $request->paperSize ?? '38x25mm',
            ]);
        } elseif ($request->isMethod('get') && is_string($request->products)) {
            $decoded = json_decode($request->products, true);
            if (is_array($decoded)) {
                $request->merge(['products' => $decoded]);
            }
        }

        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.copies' => 'required|integer|min:1|max:100',
            'paperSize' => 'nullable|string|max:50',
            'customWidth' => 'nullable|numeric|min:10|max:300',
            'customHeight' => 'nullable|numeric|min:10|max:300',
        ]);

        $svgGenerator = new BarcodeGeneratorSVG();
        $pngGenerator = new BarcodeGeneratorPNG();
        $paperSize = $request->paperSize ?? '38x25mm';

        $products = Product::whereIn('id', collect($request->products)->pluck('id'))
            ->get()
            ->map(function ($product) use ($request, $svgGenerator, $pngGenerator) {
                // Find the copies for this product
                $productRequest = collect($request->products)
                    ->firstWhere('id', $product->id);

                // Generate barcode if it doesn't exist
                if (! $product->barcode) {
                    $product->barcode = $this->generateUniqueBarcode();
                    $product->save();
                }

                // Generate vector SVG barcode for razor-sharp thermal & laser printing
                try {
                    $svgBarcode = $svgGenerator->getBarcode(
                        $product->barcode,
                        $svgGenerator::TYPE_CODE_128,
                        1.5,
                        36
                    );
                    $svgDataUri = 'data:image/svg+xml;base64,'.base64_encode($svgBarcode);
                } catch (\Throwable $e) {
                    $svgDataUri = null;
                }

                // Also generate PNG for storage & fallback
                $pngBarcode = $pngGenerator->getBarcode(
                    $product->barcode,
                    $pngGenerator::TYPE_CODE_128,
                    2,
                    45,
                    [0, 0, 0]
                );

                $path = 'barcodes/'.$product->barcode.'.png';
                Storage::disk('public')->makeDirectory('barcodes');
                Storage::disk('public')->put($path, $pngBarcode);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sku' => $product->sku,
                    'price' => $product->selling_price,
                    'copies' => (int) ($productRequest['copies'] ?? 1),
                    'image_url' => $svgDataUri ?: ('data:image/png;base64,'.base64_encode($pngBarcode)),
                    'png_url' => Storage::disk('public')->url($path),
                    'file_url' => Storage::disk('public')->url($path),
                ];
            });

        return Inertia::render('Admin/Products/BarcodePrint', [
            'products' => $products,
            'paperSize' => $paperSize,
            'initialCustomWidth' => $request->customWidth ? (float) $request->customWidth : 38,
            'initialCustomHeight' => $request->customHeight ? (float) $request->customHeight : 25,
        ]);
    }
}
