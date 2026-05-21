<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderAttachment extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'file_path',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}