<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class ClientRequest extends Model
{
    //

    protected $connection = 'portal_request_db';
    protected $table = 'app_portal_requests';

    protected $fillable = [
        'client_id',
        'member_id',
        'provider_id',
        'provider',
        'doctor_id',
        'doctor_name',
        'loa_type',
        'complaint',
        'loa_status',
        'is_excluded'
    ];
}
