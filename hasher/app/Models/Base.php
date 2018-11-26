<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
	public function scopeNotDeleted($query)
	{
		return $query->whereNull('deleted_at');
	}
}
