<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FgpoBill extends Model
{
    use HasFactory;

    protected $table = 'fgpo_bills';

    protected $fillable = [
        'vendor_id', 'bill_date', 'ref_bill_no',
    ];

    /**
     * Bill belongs to a Vendor (Chart of Account)
     */
    public function vendor()
    {
        return $this->belongsTo(ChartOfAccounts::class, 'vendor_id');
    }

    /**
     * Bill has many Details
     */
    public function details()
    {
        return $this->hasMany(FgpoBillDetail::class, 'bill_id');
    }
}
