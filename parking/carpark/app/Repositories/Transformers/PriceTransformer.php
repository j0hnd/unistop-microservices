<?php

namespace App\Repositories\Transformers;

class PriceTransformer extends Transformer
{
	public function transform($price)
	{
		return [
			'id' => $price->id,
			'price' => $price->price_value
		];
	}
}
