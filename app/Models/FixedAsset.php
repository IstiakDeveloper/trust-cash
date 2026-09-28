<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_code',
        'name',
        'branch_id',
        'status',
        'description',
        'created_by'
    ];

    protected $appends = [
        'total_purchase_price',
        'total_current_value',
        'items_count'
    ];

    public function items()
    {
        return $this->hasMany(FixedAssetItem::class);
    }

    public function activeItems()
    {
        return $this->hasMany(FixedAssetItem::class)->where('status', 'active');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalPurchasePriceAttribute(): float
    {
        return (float) $this->items()->sum('purchase_price');
    }

    public function getTotalCurrentValueAttribute(): float
    {
        return (float) $this->items()->where('status', 'active')->sum('current_value');
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Calculate asset value up to a specific date for financial reports
     */
    public function valueUpTo(Carbon $endDate): float
    {
        return (float) $this->items()
            ->where('purchase_date', '<=', $endDate)
            ->where('status', 'active')
            ->sum('current_value');
    }
}
