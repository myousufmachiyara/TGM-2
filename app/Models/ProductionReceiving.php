<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionReceiving extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'production_id', 'vendor_id', 'rec_date',
        'grn_no', 'convance_charges', 'bill_discount', 'received_by',
    ];

    protected $casts = [
        'rec_date'         => 'date:Y-m-d',
        'convance_charges' => 'decimal:2',
        'bill_discount'    => 'decimal:2',
    ];

    // ── Auto-generate grn_no ──────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (ProductionReceiving $model) {
            if (empty($model->grn_no)) {
                $last = static::withTrashed()
                    ->whereNotNull('grn_no')
                    ->where('grn_no', 'like', 'PGRN-%')
                    ->orderByDesc('id')
                    ->value('grn_no');

                $next = $last ? ((int) substr($last, 5)) + 1 : 1;
                $model->grn_no = 'PGRN-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function details()
    {
        return $this->hasMany(ProductionReceivingDetail::class, 'production_receiving_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function vouchers()
    {
        return $this->morphMany(Voucher::class, 'source');
    }
}