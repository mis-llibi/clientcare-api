<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class LoaInTransit extends Model
{
    //

    protected $connection = "ebd_current";
    protected $table = "loa_files_in_transits";
    protected $fillable = [
        'loa_files_id',
        'type',
        'document_number',
        'company_id',
        'employee_name',
        'patient_name',
        'hospital_name',
        'date',
        'time',
        'status',
    ];
}
