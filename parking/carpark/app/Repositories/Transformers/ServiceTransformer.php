<?php

namespace App\Repositories\Transformers;

class ServiceTransformer extends Transformer
{
	public function transform($services)
	{
		if ($services->isEmpty()) {
			return null;
		}

		if ($services->count() > 1) {
			foreach ($services as $service) {
				$transformed[] = [
					'id' => $service->id,
		            'service_name' => $service->service_name,
					'icon' => $service->icon
				];
			}
		} else {
			$transformed = [
	            'id' => $services->id,
	            'service_name' => $services->service_name,
				'icon' => $services->icon
	        ];
		}

		return $transformed;
	}
}
