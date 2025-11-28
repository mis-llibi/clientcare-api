<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvUploader extends Model
{
    //

    protected $table = 'csv_uploaders';


    protected $fillable = [
    'compcode',
    'inscode',
    'loanumb',
    'claimtype',
    ];
}
