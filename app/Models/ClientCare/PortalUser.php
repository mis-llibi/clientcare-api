<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PortalUser extends Model
{
    //

    protected $connection = 'portal_request_db';
    protected $table = 'users';

    protected $appends = ['full_name'];


    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->last_name}, {$this->first_name}"
        );
    }

}
