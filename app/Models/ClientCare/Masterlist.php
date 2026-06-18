<?php

namespace App\Models\ClientCare;

use Illuminate\Database\Eloquent\Model;

class Masterlist extends Model
{
    //
    protected $connection = 'sync_db';
    protected $table = 'masterlist';

    public function masterlist()
    {
        return $this->belongsTo(Masterlist::class, 'member_id', 'member_id');
    }
}
