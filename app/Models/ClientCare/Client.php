<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    //

    protected $connection = 'portal_request_db';
    protected $table = 'app_portal_clients';


    protected $fillable = [
        'request_type',
        'reference_number',
        'email',
        'alt_email',
        'contact',
        'member_id',
        'company_code',
        'first_name',
        'last_name',
        'dob',
        'is_dependent',
        'dependent_member_id',
        'dependent_first_name',
        'dependent_last_name',
        'dependent_dob',
        'remarks',
        'status',
        'platform',
        'remaining',
        'is_complaint_has_approved',
        'follow_up_request_quantity',
    ];
}
