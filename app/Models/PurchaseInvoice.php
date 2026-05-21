<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'vendor_id',
        'invoice_date',
        'payment_terms',
        'bill_no',
        'ref_no',
        'remarks',
        'convance_charges',
        'labour_charges',
        'bill_discount',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'     => 'date',
        'convance_charges' => 'decimal:2',
        'labour_charges'   => 'decimal:2',
        'bill_discount'    => 'decimal:2',
    ];

    // ── Auto-generate invoice_no ──────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (PurchaseInvoice $invoice) {
            if (empty($invoice->invoice_no)) {
                $last = static::withTrashed()
                    ->whereNotNull('invoice_no')
                    ->where('invoice_no', 'like', 'PUR-%')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->value('invoice_no');

                $next = $last ? ((int) substr($last, 4)) + 1 : 1;
                $invoice->invoice_no = 'PUR-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────

    /**
     * Vendor — points to Vendor model (not ChartOfAccounts).
     * The vendor_id FK references vendors.id.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_invoice_id');
    }

    public function attachments()
    {
        return $this->hasMany(PurchaseInvoiceAttachment::class, 'purchase_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic link to vouchers (for PostsAccountingEntries trait)
    public function vouchers()
    {
        return $this->morphMany(Voucher::class, 'source');
    }
}