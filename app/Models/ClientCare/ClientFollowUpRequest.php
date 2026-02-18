<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class ClientFollowUpRequest extends Model
{
    protected $connection = 'portal_request_db';
    protected $table = 'app_portal_follow_up_request_logs';

    protected $fillable = [
        'member_id',
        'reference_number',
    ];
}
