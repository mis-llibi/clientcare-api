<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    //

    protected $connection = "portal_request_db";
    protected $table = "app_portal_attachment";

    protected $fillable = [
        'request_id',
        'file_name',
        'file_link'
    ];
}
