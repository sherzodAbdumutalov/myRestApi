<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = [
        'firstname', 'lastname', 'middlename', 'address', 'position', 'phone_number', 'company_id', 'passport_serial_number'
    ];

    public function company(){
        return $this->belongsTo('App\Company', 'company_id', 'id');
    }
}
