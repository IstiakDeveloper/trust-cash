<?php

namespace App\Models;

use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    use PostsAccountingAtomically;

    protected $fillable = [
        'sale_return_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'subtotal',
        'sale_item_id', 'cost_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'cost_total' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
