<?php

namespace App\Models;

use Carbon\Carbon;
use DB;

class Product extends Base
{
    protected $with = [
        'carparks', 'airport', 'prices', 'closures', 'overrides'
    ];


    public function carparks()
    {
        return $this->hasMany(Carpark::class, 'id', 'carpark_id');
    }

    public function airport()
    {
        return $this->hasMany(ProductAirport::class, 'product_id');
    }

    // public function airport()
    // {
    //     return $this->belongsToMany(Airport::class, 'product_airports', 'product_id', 'airport_id')->whereNull('product_airports.deleted_at');
    // }

    public function prices()
    {
        return $this->hasMany(Price::class, 'product_id');
    }

    public function closures()
    {
        return $this->hasMany(Closure::class, 'product_id');
    }

    public function overrides()
    {
        return $this->hasMany(Override::class, 'product_id');
    }

    public function scopeNotDeactivated($query)
    {
        return $query->whereNull('deactivated_at');
    }


    /*
     * Search carparks on the given criteria
     *
     * @param Integer   $airport
     * @param Date      $startDate
     * @param Date      $endDate
     * @param Time      $startTime
     * @param Time      $endtime
     *
     * @return mixed
     *
     */
    public static function search($airport, $startDate, $endDate, $startTime, $endTime)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $noDays = $startDate->diffInDays($endDate);
        $noDays = $noDays === 0 ? 1 : $noDays;

        $products = null;
        $search = null;
        $overridePrice = null;
        $isClosed = false;
        $i = 0;

        $airport = Airport::notDeleted()
            ->notDeactivated()
            ->where('id', $airport)
            ->whereHas('product_airport', function ($q) use ($airport) {
                $q->where('airport_id', $airport);
            });

        if ($airport->exists()) {
            foreach ($airport->first()->product_airport as $pa) {
                $product = self::notDeleted()
                    ->notDeactivated()
                    ->where('id', $pa->product_id)
                    ->with(['carparks' => function ($q) use ($startTime, $endTime) {
                        $q->notDeleted();
                        $q->notDeactivated();
                        $q->whereRaw("(carparks.is_24hrs_svc = 1 OR (TIME('".$startTime."') BETWEEN opening AND closing AND TIME('".$endTime."') BETWEEN opening AND closing))");
                    }])
                    ->with(['prices' => function ($q) use ($noDays) {
                        $q->where('no_of_days', $noDays);
                    }])
                    ->first();

                if (! is_null($product)) {
                    if ($product->carparks->isNotEmpty()) {
                        foreach ($product->carparks as $carpark) {
                            // check if booking is within 24hrs
                            if ($carpark->no_bookings_not_less_than_24hrs == 1) {
                                $dropOff = Carbon::parse($startDate->format('Y-m-d').' '.$startTime.':00');
                                $timeDiff = $dropOff->diffInHours(now());

                                if ($timeDiff <= 24) {
                                    break;
                                }
                            }

                            // if prices not available move to next product
                            if ($product->prices->isEmpty()) {
                                break;
                            }

                            // get product prices
                            $prices = $product->prices;

                            // check carpark's closure dates
                            if ($product->closures->isNotEmpty()) {
                                foreach ($product->closures as $closure) {
                                    if (! is_null($closure->closed_date)) {
                                        list($startClosure, $endClosure) = explode(' - ', $closure->closed_date);
                                        $startClosure = Carbon::createFromFormat('d/m/Y', $startClosure);
                                        $endClosure = Carbon::createFromFormat('d/m/Y', $endClosure);

                                        if (($startDate->timestamp >= $startClosure->timestamp and $startDate->timestamp <= $endClosure->timestamp) or
                                            ($endDate->timestamp >= $startClosure->timestamp and $endDate->timestamp <= $endClosure->timestamp)) {

                                            $isClosed = true;
                                            break;
                                        }
                                    }
                                }
                            }

                            if ($isClosed === false) {
                                // check price overrides
                                if (count($product->overrides)) {
                                    $operator = null;
                                    $overridePrice = 0;
                                    foreach ($product->overrides as $overrides) {
                                        list($startOverride, $endOverride) = explode(' - ', $overrides->override_dates);
                                        $startOverride = Carbon::parse($startOverride);
                                        $endOverride = Carbon::parse($endOverride);

                                        for ($day = 1; $day <= $noDays; $day++) {
                                            if ($startDate->timestamp >= $startOverride->timestamp and $startDate->timestamp <= $endOverride->timestamp) {
                                                $first = substr($overrides->override_price, 0, 1);

                                                if (!is_numeric($first)) {
                                                    $overridePrice += substr($overrides->override_price, 1);
                                                } else {
                                                    $overridePrice += $overrides->override_price;
                                                }

                                                if ($overrides->override_price > 0) {
                                                    $operator = 1;
                                                } else {
                                                    $operator = 0;
                                                }
                                            }

                                            $sel = $startDate->addDays($day)->format('Y-m-d');
                                        }
                                    }

                                    if (!is_null($operator)) {
                                        if ($operator) {
                                            $overridePrice = $prices[0]->price_value + $overridePrice;
                                        } else {
                                            $overridePrice = $prices[0]->price_value - $overridePrice;
                                        }
                                    } else {
                                        $overridePrice = $prices[0]->price_value;
                                    }
                                }

                                foreach ($prices as $price) {
                                    if (!is_null($price->price_month) or !is_null($price->price_year)) {
                                        if ($price->price_month == $startDate->format('F') and $price->price_year == $startDate->format('Y')) {
                                            $key = array_search($product->id, array_column($products, 'product_id'));

                                            if ($key !== false) {
                                                unset($products[$key]);
                                            }

                                            $products[$i] = [
                                                'product_id' => $product->id,
                                                'airport_id' => '',
                                                'airport_name' => '',
                                                'carpark' => '',
                                                'image' => $product->image,
                                                'price_id' => $price->id,
                                                'prices' => $price
                                            ];
                                        }
                                    } else {
                                        $products[$i] = [
                                            'product_id' => $product->id,
                                            'airport_id' => '',
                                            'airport_name' => '',
                                            'carpark' => '',
                                            'image' => $product->image,
                                            'price_id' => $price->id,
                                            'prices' => $price
                                        ];
                                    }

                                    $i++;
                                }
                            }
                        }
                    }
                }
            }

            // if (!is_null($products) and !isset($data['sub'])) {
            //     foreach ($products as $key => $row) {
            //         $matches[$key] = $row['overrides'];
            //         if (is_null($row['overrides'])) {
            //             $price = $row['prices'];
            //             $matches[$key] = $price->price_value;
            //         }
            //     }
            //
            //     array_multisort($matches, SORT_ASC, $products);
            // }
        }

        dd($products);
        return null;
    }
}
