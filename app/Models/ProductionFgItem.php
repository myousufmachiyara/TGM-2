<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionFgItem extends Model
{
    protected $fillable = [
        'production_id', 'product_id', 'variation_id',
        'qty', 'manufacturing_rate', 'desc',
    ];

    protected $casts = [
        'qty'                => 'decimal:2',
        'manufacturing_rate' => 'decimal:2',
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
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
}