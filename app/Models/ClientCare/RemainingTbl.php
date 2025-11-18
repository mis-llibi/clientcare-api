<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class RemainingTbl extends Model
{
    //

    protected $connection = 'portal_request_db';
    protected $table = 'app_remaining';

    protected $fillable = [
        'uniquecode',
        'allow',
    ];

    public $timestamps = false;

}
