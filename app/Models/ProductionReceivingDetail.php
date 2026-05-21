<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionReceivingDetail extends Model
{
    protected $fillable = [
        'production_receiving_id', 'product_id', 'variation_id',
        'manufacturing_cost', 'received_qty', 'remarks',
    ];

    protected $casts = [
        'manufacturing_cost' => 'decimal:2',
        'received_qty'       => 'decimal:2',
    ];

    public function receiving()
    {
        return $this->belongsTo(ProductionReceiving::class, 'production_receiving_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit');
    }
}