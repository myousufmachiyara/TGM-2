<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderReceiving extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'received_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    // ── Auto-generate GRN number ──────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (PurchaseOrderReceiving $rec) {
            if (empty($rec->grn_number)) {
                $last = static::withTrashed()->latest('id')->value('id') ?? 0;
                $rec->grn_number = 'GRN-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivingItems()
    {
        return $this->hasMany(PurchaseOrderReceivingItem::class);
    }
}