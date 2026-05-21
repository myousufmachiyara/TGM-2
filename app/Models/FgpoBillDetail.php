<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FgpoBillDetail extends Model
{
    use HasFactory;

    protected $table = 'fgpo_bill_details';

    protected $fillable = [
        'bill_id', 'production_id', 'product_id',
        'rate', 'adjusted_amount' , 'received_qty'
    ];

    /**
     * Detail belongs to a Bill
     */
    public function bill()
    {
        return $this->belongsTo(FgpoBill::class, 'bill_id');
    }

    /**
     * Detail belongs to a Production (pur_fgpos)
     */
    public function production()
    {
        return $this->belongsTo(PurFgpo::class, 'production_id');
    }

    /**
     * Detail belongs to a Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
