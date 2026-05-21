<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Production extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id', 'category_id', 'order_date',
        'production_type', 'remarks', 'attachments', 'created_by',
    ];

    protected $casts = [
        'order_date'  => 'date:Y-m-d',
        'attachments' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    /** Vendor who is doing the job (CMT contractor or raw buyer) */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** Raw materials issued to vendor */
    public function rawDetails()
    {
        return $this->hasMany(ProductionDetail::class);
    }

    /** Alias kept for backward compatibility with existing code */
    public function details()
    {
        return $this->rawDetails();
    }

    /** Finished goods ordered from vendor (CMT only) */
    public function fgItems()
    {
        return $this->hasMany(ProductionFgItem::class);
    }

    /** FG receivings against this production */
    public function receivings()
    {
        return $this->hasMany(ProductionReceiving::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers()
    {
        return $this->morphMany(Voucher::class, 'source');
    }

    // ── Computed ──────────────────────────────────────────────────────

    public function getStatusAttribute(): string
    {
        if (!$this->receivings || $this->receivings->count() === 0) return 'Pending';
        $fgReceived = $this->receivings->flatMap->details->sum('received_qty');
        $fgOrdered  = $this->fgItems->sum('qty');
        if ($fgOrdered > 0 && $fgReceived >= $fgOrdered) return 'Completed';
        return 'Partial';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'Pending'   => 'badge bg-danger',
            'Partial'   => 'badge bg-warning text-dark',
            'Completed' => 'badge bg-success',
            default     => 'badge bg-secondary',
        };
    }
}