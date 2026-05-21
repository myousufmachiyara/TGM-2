<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'vendor_id',
        'name',
        'sku',
        'barcode',
        'description',
        'manufacturing_cost',
        'opening_stock',
        'selling_price',
        'consumption',
        'reorder_level',
        'max_stock_level',
        'minimum_order_qty',
        'measurement_unit',
        'item_type',   // fg | raw | service
        'is_active',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'manufacturing_cost' => 'decimal:2',
        'selling_price'      => 'decimal:2',
        'opening_stock'      => 'decimal:2',
    ];

    // ── Auto-assign barcode on create ─────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->barcode)) {
                $prefix = match ($product->item_type) {
                    'fg'      => 'FG',
                    'raw'     => 'RAW',
                    'service' => 'SRV',
                    default   => 'PRD',
                };
                $product->barcode = self::generateBarcode($prefix);
            }
        });
    }

    // ── Barcode generator using BarcodeSequence ───────────────────────
    public static function generateBarcode(string $prefix): string
    {
        $seq = BarcodeSequence::firstOrCreate(
            ['prefix' => $prefix],
            ['next_number' => 1]
        );

        $barcode = $prefix . str_pad($seq->next_number, 6, '0', STR_PAD_LEFT);
        $seq->increment('next_number');

        return $barcode;
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    // FIX: vendor is a ChartOfAccounts entry, not a Vendor model
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit');
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'item_id');
    }
}