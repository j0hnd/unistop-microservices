<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Service extends Base
{
	use SoftDeletes;

	protected $guarded = [
		'application_id'
	];

	protected $hidden = [
		'salt'
	];


	public function application()
	{
		return $this->belongsTo(Application::class, 'application_id', 'id');
	}

	public function created_by()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function scopeEnabled($query)
	{
		return $query->where('enabled', 1);
	}

	public function token()
	{
		return $this->morphToMany(Token::class, 'tokenable');
	}
}
