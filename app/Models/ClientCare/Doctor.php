<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    //

    protected $connection = "sync_db";
    protected $table = "doctors";
}
