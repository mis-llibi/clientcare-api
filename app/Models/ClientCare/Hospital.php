<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    //

    protected $connection = 'sync_db';
    protected $table = 'hospitals';
}
