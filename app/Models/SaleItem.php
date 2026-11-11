<?php

namespace App\Models;

use App\Services\SaleAccounting;
use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaleItem extends Model
{
    protected $appends = ['returnable_quantity', 'return_unit_price'];

    public function getReturnableQuantityAttribute(): string
    {
        $returned = (string) $this->returnedItems()->sum('quantity');

        return bcsub((string) $this->quantity, $returned, 6);
    }

    public function returnedItems()
    {
        return SaleReturnItem::where(fn ($query) => $query->where('sale_item_id', $this->id)
            ->orWhereIn('id', DB::table('legacy_sale_return_links')->where('sale_item_id', $this->id)->select('return_item_id')));
    }

    public function getReturnUnitPriceAttribute(): string
    {
        $entry = DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('source_id', $this->id)->first();

        return $entry && bccomp((string) $this->quantity, '0', 6) > 0
            ? bcdiv(bcadd((string) $entry->net, (string) $entry->tax, 6), (string) $this->quantity, 6)
            : (string) $this->unit_price;
    }

    use PostsAccountingAtomically;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'subtotal', 'cost_total', 'costing_method', 'lot_id', 'discount_amount', 'tax_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'cost_total' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(fn ($item) => app(SaleAccounting::class)->record($item));
        static::deleting(fn ($item) => app(SaleAccounting::class)->reverse($item));
    }

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
