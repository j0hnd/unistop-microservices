<?php

namespace App\Models;

use Carbon\Carbon;
use App\Repositories\Transformers\ServiceTransformer;
use App\Repositories\Transformers\PriceTransformer;

class Product extends Base
{
    protected $with = [
        'carpark', 'airport', 'prices', 'closures', 'overrides', 'carpark_services'
    ];


    public function carpark()
    {
        return $this->hasOne(Carpark::class, 'id', 'carpark_id');
    }

    public function airport()
    {
        return $this->hasMany(ProductAirport::class, 'product_id');
    }

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

    public function carpark_services()
    {
        return $this->belongsToMany(CarparkService::class, 'services', 'product_id', 'service_id')->whereNull('services.deleted_at');
    }

    public function scopeNotDeactivated($query)
    {
        return $query->whereNull('deactivated_at');
    }

    /*
     * Search carparks on the given criteria
     *
     * @param int $airport
     * @param Carbon\Carbon $startDate
     * @param Carbon\Carbon $endDate
     * @param string $startTime
     * @param string $endtime
     *
     * @return array
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
        $i = 0;

        /**
         * @var App\Repositories\Transformers\ServiceTransformer
         */
        $serviceTransformer = new ServiceTransformer();

        /**
         * @var App\Repositories\Transformers\PriceTransformer
         */
        $priceTransformer = new PriceTransformer();


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
                    ->with(['carpark' => function ($q) use ($startTime, $endTime) {
                        $q->notDeleted();
                        $q->notDeactivated();
                        $q->whereRaw("(carparks.is_24hrs_svc = 1 OR (TIME('".$startTime."') BETWEEN opening AND closing AND TIME('".$endTime."') BETWEEN opening AND closing))");
                    }])
                    ->with(['prices' => function ($q) use ($noDays) {
                        $q->where('no_of_days', $noDays);
                    }])
                    ->first();

                if (! is_null($product)) {
                    if ($product->carpark->exists()) {
                        $carpark = $product->carpark;

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

                        if (self::validateClosures($product->closures, $startDate, $endDate) === false) {
                            // check price overrides
                            $overridePrice = self::getOverrides($product->overrides, $product->prices, $startDate, $endDate, $noDays);

                            foreach ($product->prices as $price) {
                                if (!is_null($price->price_month) or !is_null($price->price_year)) {
                                    if ($price->price_month == $startDate->format('F') and $price->price_year == $startDate->format('Y')) {
                                        $key = array_search($product->id, array_column($products, 'product_id'));

                                        if ($key !== false) {
                                            unset($products[$key]);
                                        }

                                        $products[$i] = [
                                            'product_id' => $product->id,
                                            'airport_id' => $pa->airport_id,
                                            'airport_name' => $pa->airport->airport_name,
                                            'carpark' => $carpark->name,
                                            'drop_off' => $startDate." ".$startTime,
                                            'return_at' => $endDate." ".$endTime,
                                            'short_description' => $product->short_description,
                                            'description' => $product->description,
                                            'on_arrival' => $product->on_arrival,
                                            'on_return' => $product->on_return,
                                            'latitude' => $pa->airport->latitude,
                                            'longitude' => $pa->airport->longitude,
                                            'image' => $product->image,
                                            'price' => $priceTransformer->transform($price),
                                            'overrides' => $overridePrice,
                                            'services' => $serviceTransformer->transform($product->carpark_services)
                                        ];
                                    }
                                } else {
                                    $products[$i] = [
                                        'product_id' => $product->id,
                                        'airport_id' => $pa->airport_id,
                                        'airport_name' => $pa->airport->airport_name,
                                        'carpark' => $carpark->name,
                                        'drop_off' => $startDate." ".$startTime,
                                        'return_at' => $endDate." ".$endTime,
                                        'short_description' => $product->short_description,
                                        'description' => $product->description,
                                        'on_arrival' => $product->on_arrival,
                                        'on_return' => $product->on_return,
                                        'latitude' => $pa->airport->latitude,
                                        'longitude' => $pa->airport->longitude,
                                        'image' => $product->image,
                                        'price' => $priceTransformer->transform($price),
                                        'overrides' => $overridePrice,
                                        'services' => $serviceTransformer->transform($product->carpark_services)
                                    ];
                                }

                                $i++;
                            }
                        }
                    }
                }
            }

            if (!is_null($products)) {
                foreach ($products as $key => $row) {
                    $matches[$key] = $row['overrides'];
                    if (is_null($row['overrides'])) {
                        $price = $row['prices'];
                        $matches[$key] = $price->price_value;
                    }
                }

                array_multisort($matches, SORT_ASC, $products);
            }
        }

        return $products;
    }

    /*
     * Check if carpark has a closure dates
     *
     * @param Illuminate\Support\Collection $closures
     * @param Carbon\Carbon $startDate
     * @param Carbon\Carbon $endDate
     *
     * @return bool
     */
    private static function validateClosures($closures, $startDate, $endDate)
    {
        $isClosed = false;

        if ($closures->isNotEmpty()) {
            foreach ($closures as $closure) {
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

        return $isClosed;
    }

    /*
     * Calculates product's price overrides
     *
     * @param lluminate\Support\Collection $overrides
     * @param lluminate\Support\Collection $prices
     * @param Carbon\Carbon $startDate
     * @param Carbon\Carbon $endDate
     * @param int $noDays
     *
     * @return float
     */
    private static function getOverrides($overrides, $prices, $startDate, $endDate, $noDays)
    {
        $operator = null;
        $overridePrice = 0;

        if (count($overrides)) {
            foreach ($overrides as $overrides) {
                list($startOverride, $endOverride) = explode(' - ', $overrides->override_dates);
                $startOverride = Carbon::createFromFormat('d/m/Y', $startOverride);
                $endOverride = Carbon::createFromFormat('d/m/Y', $endOverride);

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

        return $overridePrice;
    }
}
