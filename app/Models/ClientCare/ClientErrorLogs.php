<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class ClientErrorLogs extends Model
{
    //
    protected $connection = 'portal_request_db';
    protected $table = 'app_portal_clients_error_logs';

    protected $fillable = [
        'request_type',
        'member_id',
        'first_name',
        'last_name',
        'status',
        'dob',
        'is_dependent',
        'dependent_member_id',
        'dependent_first_name',
        'dependent_last_name',
        'dependent_dob',
        'request_loa_type',
        'email',
        'company',
        'mobile',
        'fullname',
        'deps_fullname',
        'is_allow_to_call',
        'notify_status'
    ];
}
