<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeController extends Controller
{
    public function generate(Product $product)
    {
        $generator = new BarcodeGeneratorPNG;

        if (! $product->barcode) {
            $product->barcode = $this->generateUniqueBarcode();
            $product->save();
        }

        // Generate a compact barcode
        $barcode = $generator->getBarcode(
            $product->barcode,
            $generator::TYPE_CODE_128,
            1,     // Reduced bar width for more compact size
            30,    // Reduced height
            [0, 0, 0]  // Black color
        );

        // Store barcode image
        $path = 'barcodes/'.$product->barcode.'.png';
        Storage::disk('public')->makeDirectory('barcodes');

        // Add small white padding
        $originalImage = imagecreatefromstring($barcode);
        $width = imagesx($originalImage);
        $height = imagesy($originalImage);

        // Create new image with minimal padding
        $paddedImage = imagecreatetruecolor($width + 10, $height + 10);
        $white = imagecolorallocate($paddedImage, 255, 255, 255);
        imagefill($paddedImage, 0, 0, $white);

        // Copy original barcode to padded image
        imagecopy($paddedImage, $originalImage, 5, 5, 0, 0, $width, $height);

        // Save as PNG
        ob_start();
        imagepng($paddedImage, null, 0);
        $barcodeData = ob_get_clean();

        // Clean up
        imagedestroy($originalImage);
        imagedestroy($paddedImage);

        Storage::disk('public')->put($path, $barcodeData);

        return response()->json([
            'barcode' => $product->barcode,
            'image_url' => Storage::disk('public')->url($path),
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
                'paperSize' => $request->paperSize ?? 'A4',
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
            'paperSize' => 'nullable|in:A4,Letter,80mm',
        ]);

        $generator = new BarcodeGeneratorPNG();
        $paperSize = $request->paperSize ?? 'A4';

        $products = Product::whereIn('id', collect($request->products)->pluck('id'))
            ->get()
            ->map(function ($product) use ($request, $generator) {
                // Find the copies for this product
                $productRequest = collect($request->products)
                    ->firstWhere('id', $product->id);

                // Generate barcode if it doesn't exist
                if (! $product->barcode) {
                    $product->barcode = $this->generateUniqueBarcode();
                    $product->save();
                }

                $barcode = $generator->getBarcode(
                    $product->barcode,
                    $generator::TYPE_CODE_128,
                    1,
                    30,
                    [0, 0, 0]
                );

                $path = 'barcodes/'.$product->barcode.'.png';
                Storage::disk('public')->makeDirectory('barcodes');

                $originalImage = imagecreatefromstring($barcode);
                $width = imagesx($originalImage);
                $height = imagesy($originalImage);

                $paddedImage = imagecreatetruecolor($width + 10, $height + 10);
                $white = imagecolorallocate($paddedImage, 255, 255, 255);
                imagefill($paddedImage, 0, 0, $white);
                imagecopy($paddedImage, $originalImage, 5, 5, 0, 0, $width, $height);

                ob_start();
                imagepng($paddedImage, null, 0);
                $barcodeData = ob_get_clean();

                imagedestroy($originalImage);
                imagedestroy($paddedImage);

                Storage::disk('public')->put($path, $barcodeData);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sku' => $product->sku,
                    'price' => $product->selling_price,
                    'copies' => (int) ($productRequest['copies'] ?? 1),
                    'image_url' => 'data:image/png;base64,'.base64_encode($barcodeData),
                    'file_url' => Storage::disk('public')->url($path),
                ];
            });

        return Inertia::render('Admin/Products/BarcodePrint', [
            'products' => $products,
            'paperSize' => $paperSize,
        ]);
    }
}
