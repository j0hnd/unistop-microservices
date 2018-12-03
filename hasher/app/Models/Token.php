<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Token extends Authenticatable
{
    use SoftDeletes;

    public function application()
    {
        return $this->morphedByMany(Application::class, 'tokenable');
    }

    public function services()
    {
        return $this->morphedByMany(Service::class, 'tokenable');
    }
}
