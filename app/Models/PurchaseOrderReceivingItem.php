<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderReceivingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_receiving_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function receiving()
    {
        return $this->belongsTo(PurchaseOrderReceiving::class, 'purchase_order_receiving_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}