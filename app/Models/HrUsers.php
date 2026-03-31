<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\ClientCare\Client;

class HrUsers extends Authenticatable
{
    //
    protected $connection = 'portal_request_db';
    protected $table = 'hr_users';

    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password'
    ];

    public function viewingClient()
    {
        return $this->hasOne(Client::class, 'view_by');
    }
}
