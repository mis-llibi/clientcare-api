<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Callback extends Model
{
    //

    protected $connection = 'portal_request_db';
    protected $table = 'app_portal_callback';

    protected $fillable = [
        'client_id',
        'failed_count'
    ];
}
