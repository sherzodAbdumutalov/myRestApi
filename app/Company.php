<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    protected $fillable = [
        'name', 'owner_firstname', 'owner_lastname', 'owner_middlename', 'address', 'website', 'phone_number', 'email', 'id'
    ];

    public function employees(){
        return $this->hasMany('App\Employee', 'company_id');
    }
}
