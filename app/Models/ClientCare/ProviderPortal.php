<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class ProviderPortal extends Model
{
    //

    protected $connection = "provider_portal_db";
    protected $table = "provider_portals";

    protected $hidden = [
        'password'
    ];
}
