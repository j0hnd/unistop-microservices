<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Token extends Base
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
