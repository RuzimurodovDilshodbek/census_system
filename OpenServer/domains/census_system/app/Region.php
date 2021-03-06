<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $guarded = [];

    public function citizens() {
        return $this->hasMany('App\Citizen');
    }
}
