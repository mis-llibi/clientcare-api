<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvUploader extends Model
{
    //

    protected $table = 'csv_uploaders';
    protected $connection = "portal_request_db";


    protected $fillable = [
    'compcode',
    'inscode',
    'loanumb',
    'claimtype',
    ];
}
