<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class HatiApiLogs extends Model
{
    //

    protected $connection = "portal_request_db";
    protected $table = "hati_api_logs";

    protected $fillable = [
        'ip_address',
        'link_parameter'
    ];
}
