<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return url('/storage/' . ltrim($this->logo, '/'));
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
