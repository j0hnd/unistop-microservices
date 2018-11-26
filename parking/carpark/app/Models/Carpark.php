<?php

namespace App\Models;

class Carpark extends Base
{
    public function product()
    {
        return $this->belongsTo(Product::class, 'carpark_id');
    }

    public function scopeIs24HrsService($query)
    {
        return $query->where('is_24hrs_svc', 1);
    }

    public function scopeNoBookingsNotLessThan24hrs($query)
    {
        return $query->where('no_bookings_not_less_than_24hrs', 1);
    }

    public function scopeNotDeactivated($query)
    {
        return $query->whereNull('deactivated_at');
    }
}
