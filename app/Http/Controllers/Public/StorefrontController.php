<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PendingSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StorefrontController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
        $products = Product::query()
            ->with([
                'primaryImage',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('status', true)
            ->select('products.*')
            ->selectRaw('(
                SELECT ps.available_quantity
                FROM product_stocks ps
                WHERE ps.product_id = products.id
                ORDER BY ps.id DESC
                LIMIT 1
            ) as available_quantity')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', (int) $request->input('category_id'));
            })
            ->latest()
            ->paginate(24);

        $products->getCollection()->transform(function ($p) {
            $img = $p->primaryImage ?: $p->images->first();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type ?? 'product',
                'is_service' => $p->isService(),
                'sku' => $p->sku,
                'selling_price' => (float) $p->selling_price,
                'image' => $img?->image,
                'image_url' => $img ? asset('storage/'.$img->image) : null,
                'available_quantity' => $p->isService() ? 999999 : (int) round((float) ($p->available_quantity ?? 0)),
            ];
        });

        [$cartItems, $cartSubtotal] = $this->buildCartView($request);

        $shopPhone = business_phone();
        $shopWhatsApp = preg_replace('/[^0-9]/', '', $shopPhone);
        if (!empty($shopWhatsApp) && !str_starts_with($shopWhatsApp, '88')) {
            $shopWhatsApp = '88' . ltrim($shopWhatsApp, '0');
        }

        return Inertia::render('Public/Home', [
            'products' => $products,
            'categories' => $categories,
            'query' => $request->only(['q', 'category_id']),
            'cartCount' => $this->cartCount($request),
            'cart' => [
                'items' => $cartItems,
                'subtotal' => $cartSubtotal,
            ],
            'shop' => [
                'name' => business_name(),
                'phone' => $shopPhone,
                'whatsapp' => $shopWhatsApp,
                'address' => business_address(),
                'email' => business_email(),
            ],
        ]);
    }

    public function cart(Request $request)
    {
        [$items, $subtotal] = $this->buildCartView($request);

        return Inertia::render('Public/Cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:999',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $isService = $product->isService();
        $qty = (int) ($data['quantity'] ?? 1);
        $available = $isService ? 999999 : $this->getAvailableStock((int) $data['product_id']);
        if (! $isService && $available <= 0) {
            return back()->with('error', 'Out of stock.');
        }

        $cart = $this->getCart($request);
        $id = (string) $data['product_id'];
        $desired = min(999, ((int) ($cart[$id] ?? 0)) + $qty);
        if (! $isService && $desired > $available) {
            return back()->with('error', "Only {$available} available in stock.");
        }
        $cart[$id] = $desired;
        $request->session()->put('public_cart', $cart);

        return back()->with('success', 'Added to cart.');
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
        ]);

        $cart = [];
        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            $isService = $product?->isService();
            $available = $isService ? 999999 : $this->getAvailableStock((int) $item['product_id']);
            if (! $isService && $available <= 0) {
                return back()->with('error', 'Some items are out of stock.');
            }
            if (! $isService && (int) $item['quantity'] > $available) {
                return back()->with('error', "Quantity exceeds stock for a product (available: {$available}).");
            }
            $cart[(string) $item['product_id']] = (int) $item['quantity'];
        }
        $request->session()->put('public_cart', $cart);

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
        ]);

        $cart = $this->getCart($request);
        unset($cart[(string) $data['product_id']]);
        $request->session()->put('public_cart', $cart);

        return back()->with('success', 'Removed from cart.');
    }

    public function checkout(Request $request)
    {
        $cart = $this->getCart($request);
        if (count($cart) === 0) {
            return to_route('cart')->with('error', 'আপনার কার্ট খালি। অর্ডার করতে অনুগ্রহ করে পণ্য যুক্ত করুন।');
        }

        [$cartItems, $cartSubtotal] = $this->buildCartView($request);
        if (count($cartItems) === 0) {
            return to_route('cart')->with('error', 'আপনার কার্ট খালি।');
        }

        $shopPhone = business_phone();
        $shopWhatsApp = preg_replace('/[^0-9]/', '', $shopPhone);
        if (!empty($shopWhatsApp) && !str_starts_with($shopWhatsApp, '88')) {
            $shopWhatsApp = '88' . ltrim($shopWhatsApp, '0');
        }

        return Inertia::render('Public/Checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $cartSubtotal,
            'cartCount' => $this->cartCount($request),
            'deliveryCharges' => [
                'inside_dhaka' => 70,
                'outside_dhaka' => 130,
            ],
            'shop' => [
                'name' => business_name(),
                'phone' => $shopPhone,
                'whatsapp' => $shopWhatsApp,
                'address' => business_address(),
                'email' => business_email(),
            ],
        ]);
    }

    public function submitOrder(Request $request)
    {
        $cart = $this->getCart($request);
        if (count($cart) === 0) {
            return to_route('cart')->with('error', 'আপনার কার্ট খালি।');
        }

        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'address' => 'required|string|min:5|max:500',
            'delivery_area' => 'required|string|in:inside_dhaka,outside_dhaka',
            'payment_method' => 'required|string|in:cod,bkash,nagad',
            'payment_trx' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'আপনার নাম প্রদান করুন।',
            'phone.required' => 'মোবাইল নম্বর প্রদান করুন।',
            'phone.regex' => 'সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (যেমন: 017xxxxxxxx)।',
            'address.required' => 'ডেলিভারির পূর্ণ ঠিকানা প্রদান করুন।',
            'address.min' => 'অনুগ্রহ করে বিস্তারিত ঠিকানা লিখুন।',
            'delivery_area.required' => 'ডেলিভারি এরিয়া সিলেক্ট করুন।',
            'payment_method.required' => 'পেমেন্ট মাধ্যম সিলেক্ট করুন।',
        ]);

        $productIds = array_keys($cart);
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->select('id', 'name', 'selling_price')
            ->get()
            ->keyBy('id');

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $itemsPayload = [];
            foreach ($cart as $productId => $qty) {
                $p = $products->get((int) $productId);
                if (! $p) {
                    continue;
                }
                $unitPrice = (float) $p->selling_price;
                $lineSubtotal = $unitPrice * (int) $qty;
                $subtotal += $lineSubtotal;
                $itemsPayload[] = [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if (count($itemsPayload) === 0) {
                throw new \Exception('কার্টে কোনো সঠিক পণ্য পাওয়া যায়নি।');
            }

            // Customer find or create by phone
            $phone = trim($data['phone']);
            $customer = Customer::where('phone', $phone)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'name' => trim($data['name']),
                    'phone' => $phone,
                    'address' => trim($data['address']),
                    'status' => true,
                    'balance' => 0,
                    'credit_limit' => 0,
                ]);
            } else {
                $updates = [];
                if (empty($customer->address)) {
                    $updates['address'] = trim($data['address']);
                }
                if ($customer->name === 'Walk-in Customer' || empty($customer->name)) {
                    $updates['name'] = trim($data['name']);
                }
                if (!empty($updates)) {
                    $customer->update($updates);
                }
            }

            $deliveryCharge = $data['delivery_area'] === 'outside_dhaka' ? 130 : 70;
            $discount = 0;
            $total = $subtotal + $deliveryCharge - $discount;

            $orderNo = 'ORD-' . date('Ymd') . '-' . Str::upper(Str::random(5));

            $areaLabel = $data['delivery_area'] === 'outside_dhaka' ? 'ঢাকার বাইরে (৳১৩০)' : 'ঢাকার ভেতরে (৳৭০)';
            $methodLabel = match($data['payment_method']) {
                'bkash' => 'বিকাশ (bKash)',
                'nagad' => 'নগদ (Nagad)',
                default => 'ক্যাশ অন ডেলিভারি (Cash on Delivery)',
            };

            $noteLines = [
                "ডেলিভারি এরিয়া: {$areaLabel}",
                "ডেলিভারি চার্জ: ৳{$deliveryCharge}",
                "ডেলিভারি ঠিকানা: {$data['address']}",
                "পেমেন্ট মেথড: {$methodLabel}",
            ];
            if (!empty($data['payment_trx'])) {
                $noteLines[] = "TrxID: {$data['payment_trx']}";
            }
            if (!empty($data['note'])) {
                $noteLines[] = "কাস্টমার নোট: {$data['note']}";
            }
            $fullNote = implode("\n", $noteLines);

            $pending = PendingSale::create([
                'public_order_no' => $orderNo,
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'note' => $fullNote,
            ]);

            foreach ($itemsPayload as $i) {
                $pending->items()->create($i);
            }

            DB::commit();

            $request->session()->forget('public_cart');

            $successRoute = route('order.success', ['orderNo' => $orderNo], false);
            return redirect($successRoute)->with('success', "আপনার অর্ডারটি সফলভাবে সম্পন্ন হয়েছে!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function orderSuccess(string $orderNo)
    {
        $order = PendingSale::where('public_order_no', $orderNo)
            ->with([
                'customer',
                'items.product.primaryImage',
                'items.product.images',
            ])
            ->firstOrFail();

        $items = $order->items->map(function ($it) {
            $p = $it->product;
            $img = $p?->primaryImage ?: $p?->images?->first();
            return [
                'id' => $it->id,
                'product_name' => $p?->name ?? 'পণ্য',
                'sku' => $p?->sku,
                'image_url' => $img ? asset('storage/'.$img->image) : null,
                'quantity' => (int) $it->quantity,
                'unit_price' => (float) $it->unit_price,
                'subtotal' => (float) $it->subtotal,
            ];
        });

        $deliveryCharge = max(0, (float) $order->total - (float) $order->subtotal + (float) $order->discount);

        $shopPhone = business_phone();
        $shopWhatsApp = preg_replace('/[^0-9]/', '', $shopPhone);
        if (!empty($shopWhatsApp) && !str_starts_with($shopWhatsApp, '88')) {
            $shopWhatsApp = '88' . ltrim($shopWhatsApp, '0');
        }

        return Inertia::render('Public/OrderSuccess', [
            'order' => [
                'order_no' => $order->public_order_no,
                'date' => $order->created_at->format('d M, Y h:i A'),
                'subtotal' => (float) $order->subtotal,
                'delivery_charge' => (float) $deliveryCharge,
                'total' => (float) $order->total,
                'status' => $order->status,
                'note' => $order->note,
                'customer' => [
                    'name' => $order->customer?->name,
                    'phone' => $order->customer?->phone,
                    'address' => $order->customer?->address,
                ],
                'items' => $items,
            ],
            'shop' => [
                'name' => business_name(),
                'phone' => $shopPhone,
                'whatsapp' => $shopWhatsApp,
                'address' => business_address(),
                'email' => business_email(),
            ],
        ]);
    }

    private function getCart(Request $request): array
    {
        $cart = $request->session()->get('public_cart', []);
        return is_array($cart) ? $cart : [];
    }

    /**
     * @return array{0: array<int, array{product: array{id:int,name:string,sku:?string,selling_price:float,image:?string}, quantity:int, line_total:float}>, 1: float}
     */
    private function buildCartView(Request $request): array
    {
        $cart = $this->getCart($request);
        $productIds = array_keys($cart);

        if (count($productIds) === 0) {
            return [[], 0.0];
        }

        $products = Product::query()
            ->with([
                'primaryImage',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $p = $products->get((int) $productId);
            if (! $p) {
                continue;
            }
            $available = $this->getAvailableStock((int) $productId);
            $line = (float) $p->selling_price * (int) $qty;
            $subtotal += $line;
            $items[] = [
                'product' => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'selling_price' => (float) $p->selling_price,
                    'image' => ($p->primaryImage ?: $p->images->first())?->image,
                    'image_url' => ($p->primaryImage ?: $p->images->first())
                        ? asset('storage/'.(($p->primaryImage ?: $p->images->first())->image))
                        : null,
                    'available_quantity' => (int) $available,
                ],
                'quantity' => (int) $qty,
                'line_total' => $line,
            ];
        }

        return [$items, $subtotal];
    }

    private function getAvailableStock(int $productId): int
    {
        $product = Product::find($productId);
        if ($product && $product->isService()) {
            return 999999;
        }

        $value = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->value('available_quantity');

        return (int) round((float) ($value ?? 0));
    }

    private function cartCount(Request $request): int
    {
        $cart = $this->getCart($request);
        return array_sum(array_map('intval', $cart));
    }
}

