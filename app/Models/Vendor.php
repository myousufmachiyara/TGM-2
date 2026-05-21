<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_no',
        'address',
        'opening_payables',
        'opening_date',
        'remarks',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_payables'    => 'decimal:2',
        'is_active'           => 'boolean',
        'opening_date'        => 'date',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }
    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}