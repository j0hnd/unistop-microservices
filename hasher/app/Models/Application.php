<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Application extends Base
{
    use SoftDeletes;

    protected $with = [
    	'services'
	];


    public function services()
	{
		return $this->hasMany(Service::class, 'application_id', 'id');
	}

	public function owner()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public static function getToken($application_name, $service_name)
	{
		$application = self::where('slug', $application_name)
			->whereHas('services', function ($query) use ($service_name) {
				$query->where('slug', $service_name);
				$query->notDeleted();
				$query->enabled();
			})
			->first();

		$service = $application->services->where('slug', $service_name)->first();

		return $service->token->first()->token;
	}
}
