<?php
// ============================================================
//  PurchaseOrder.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'category_id',
        'order_date',
        'ordered_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    // ── Auto-generate PO number ───────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $po) {
            if (empty($po->po_number)) {
                $last   = static::withTrashed()->latest('id')->value('id') ?? 0;
                $po->po_number = 'PO-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(PurchaseOrderAttachment::class);
    }

    public function receivings()
    {
        return $this->hasMany(PurchaseOrderReceiving::class);
    }

    // ── Computed status ───────────────────────────────────────────────

    public function getStatusAttribute(): string
    {
        $ordered  = (float) $this->items->sum('quantity');
        $received = (float) $this->receivings
            ->flatMap->receivingItems
            ->sum('quantity');

        if ($received <= 0)              return 'Pending';
        if ($received < $ordered)        return 'Partially Received';
        return 'Completed';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Pending'             => 'badge bg-danger',
            'Partially Received'  => 'badge bg-warning text-dark',
            'Completed'           => 'badge bg-success',
            default               => 'badge bg-secondary',
        };
    }
}