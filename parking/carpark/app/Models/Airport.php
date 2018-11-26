<?php

namespace App\Models;

class Airport extends Base
{
    protected $with = [
        'product_airport'
    ];

    public function product_airport()
    {
        return $this->hasMany(ProductAirport::class, 'airport_id');
    }

    public function scopeNotDeactivated($query)
    {
        return $query->whereNull('deactivated_at');
    }
}
