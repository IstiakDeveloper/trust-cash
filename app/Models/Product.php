<?php

namespace App\Models;

use App\Services\InventoryReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'slug',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_id',
        'cost_price',
        'selling_price',
        'alert_quantity',
        'description',
        'specifications',
        'status',
    ];

    public function isService(): bool
    {
        return ($this->type ?? 'product') === 'service';
    }

    public function isPhysical(): bool
    {
        return ($this->type ?? 'product') !== 'service';
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function scopePhysical($query)
    {
        return $query->where('type', '!=', 'service');
    }

    protected $casts = [
        'specifications' => 'array',
        'status' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    protected $appends = ['image_url'];

    protected static function booted(): void
    {
        static::updating(function ($product) {
            if ($product->isDirty('type') && ($product->productStocks()->exists()
                || DB::table('sale_accounting_entries')->where('product_id', $product->id)->exists())) {
                throw ValidationException::withMessages(['type' => 'A product with posted history cannot change between physical stock and service. Create a separate product.']);
            }
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
            return url('/storage/'.ltrim($this->primaryImage->image, '/'));
        }

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

            return $primary ? url('/storage/'.ltrim($primary->image, '/')) : null;
        }

        return null;
    }

    // Existing Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'id');
    }

    public function sellableLots()
    {
        return $this->productStocks()->where('lot_remaining_quantity', '>', 0)->orderBy('date')->orderBy('id')->get()
            ->map(fn ($lot) => [
                'id' => $lot->id, 'number' => $lot->lot_number, 'variant_id' => $lot->product_variant_id,
                'quantity' => $lot->lot_remaining_quantity, 'selling_price' => $lot->selling_price ?? $this->selling_price,
            ])->values();
    }

    public function availableQuantity(): string
    {
        return (string) DB::table('inventory_balances')->where('product_id', $this->id)->sum('quantity');
    }

    // Stock Analysis Methods
    public static function getProductAnalysis($startDate, $endDate)
    {
        return app(InventoryReport::class)->analysis($startDate, $endDate);
    }

    public static function calculateTotals($products)
    {
        return [
            'before_quantity' => (float) $products->sum('before_quantity'),
            'before_value' => (float) $products->sum('before_value'),
            'buy_quantity' => (float) $products->sum('buy_quantity'),
            'total_buy_price' => (float) $products->sum('total_buy_price'),
            'sale_quantity' => (float) $products->sum('sale_quantity'),
            'total_sale_price' => (float) $products->sum('total_sale_price'),
            'sale_discount' => (float) $products->sum('sale_discount'),
            'sale_after_discount' => (float) $products->sum('sale_after_discount'),
            'total_profit' => (float) $products->sum('total_profit'),
            'available_quantity' => (float) $products->sum('available_quantity'),
            'available_stock_value' => (float) $products->sum('available_stock_value'),
        ];
    }

    // Accessor for current stock
    public function getCurrentStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public static function getCumulativeTotals()
    {
        $analysis = static::getProductAnalysis('1000-01-01', Carbon::now()->endOfDay());

        return ['total_profit' => ['period' => '0', 'cumulative' => $analysis['totals']['total_profit']]];
    }
}
