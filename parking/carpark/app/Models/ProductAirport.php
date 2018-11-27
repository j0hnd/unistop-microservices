<?php

namespace App\Models;

class ProductAirport extends Base
{
    // protected $with = [
    //     'products', 'prices'
    // ];

    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // public function products()
	// {
	// 	return $this->hasMany(Product::class, 'id', 'product_id');
	// }

    // public function prices()
	// {
	// 	return $this->hasMany(Price::class, 'product_id');
	// }

    public function airport()
    {
        return $this->belongsTo(Airport::class, 'airport_id');
    }
}
