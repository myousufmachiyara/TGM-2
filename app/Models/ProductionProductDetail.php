<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionProductDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'product_id',
        'variation_id',
        'manufacturing_cost',
        'order_qty',
        'consumption',
        'remarks',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
