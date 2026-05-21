<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'width',
        'description',
        'unit_price',
        'quantity',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity'   => 'decimal:2',
        'width'      => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ── Computed ──────────────────────────────────────────────────────

    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}