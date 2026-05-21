<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id', 'return_date', 'return_no', 'ref_no',
        'bill_no', 'remarks', 'convance_charges', 'bill_discount', 'created_by',
    ];

    protected $casts = [
        'return_date'      => 'date:Y-m-d',
        'convance_charges' => 'decimal:2',
        'bill_discount'    => 'decimal:2',
    ];

    // ── Auto-generate return_no ───────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (PurchaseReturn $model) {
            if (empty($model->return_no)) {
                $last = static::withTrashed()
                    ->whereNotNull('return_no')
                    ->where('return_no', 'like', 'PR-%')
                    ->orderByDesc('id')
                    ->value('return_no');

                $next = $last ? ((int) substr($last, 3)) + 1 : 1;
                $model->return_no = 'PR-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(PurchaseReturnAttachment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers()
    {
        return $this->morphMany(Voucher::class, 'source');
    }
}