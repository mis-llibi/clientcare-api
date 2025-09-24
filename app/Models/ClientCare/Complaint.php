<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    //

    protected $connection = "portal_request_db";
    protected $table = "app_portal_complaints";
}
