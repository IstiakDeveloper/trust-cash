<?php

namespace App\Models;

use App\Services\InventoryValuation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProductStock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'total_quantity',
        'total_cost',
        'unit_cost',
        'note',
        'created_by',
        'available_quantity',
        'type',
        'date',
        'bank_account_id',
        'costing_method', 'inventory_value', 'lot_remaining_quantity', 'lot_remaining_value',
        'lot_number', 'selling_price', 'sale_item_id', 'purchase_item_id', 'source_stock_id', 'sale_return_item_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:6', 'available_quantity' => 'decimal:6',
        'total_cost' => 'decimal:6', 'unit_cost' => 'decimal:8',
        'inventory_value' => 'decimal:6', 'lot_remaining_quantity' => 'decimal:6',
        'lot_remaining_value' => 'decimal:6', 'selling_price' => 'decimal:2',
    ];

    public function save(array $options = [])
    {
        if ($this->exists) {
            if ($this->costing_method && $this->isDirty(['quantity', 'total_cost', 'unit_cost', 'date', 'product_id', 'product_variant_id', 'type', 'sale_item_id', 'source_stock_id', 'costing_method', 'available_quantity', 'inventory_value', 'lot_remaining_quantity', 'lot_remaining_value'])) {
                throw new \LogicException('Posted inventory is immutable. Use a linked reversal or correction.');
            }

            return parent::save($options);
        }

        return DB::transaction(function () use ($options) {
            $valuation = app(InventoryValuation::class);
            $allocations = $valuation->prepare($this);
            $saved = parent::save($options);
            if (! $saved) {
                throw new \RuntimeException('Stock posting was cancelled.');
            }
            $valuation->finish($this, $allocations);

            return $saved;
        });
    }

    protected static function booted(): void
    {
        static::deleting(function ($stock) {
            if ($stock->costing_method) {
                throw new \LogicException('Posted inventory cannot be deleted. Use a linked reversal or correction.');
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    // Add the createdBy relationship
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
