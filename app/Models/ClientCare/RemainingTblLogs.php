<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class RemainingTblLogs extends Model
{
    //

    protected $connection = 'portal_request_db';
    protected $table = 'remaining_tbl_logs';

    protected $fillable = [
        'member_id',
    ];


}
