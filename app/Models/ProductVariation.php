<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'manufacturing_cost',
        'selling_price',
        'stock_quantity',
    ];

    // ── Auto-assign barcode on create ─────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (ProductVariation $variation) {
            if (empty($variation->barcode)) {
                // FIX: use Product::generateBarcode() — no undefined global function
                $type   = $variation->product?->item_type ?? 'prd';
                $prefix = strtoupper($type) . '-VAR';
                $variation->barcode = Product::generateBarcode($prefix);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variation_attribute_values'
        )->withTimestamps();
    }

    // Pivot model for extra handling
    public function values()
    {
        return $this->hasMany(ProductVariationAttributeValue::class);
    }

    public function receivings()
    {
        return $this->hasMany(ProductionReceivingDetail::class, 'variation_id');
    }
}