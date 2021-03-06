<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Citizen extends Model
{
    protected $guarded = ['id'];

    public static function rules()
    {
        return [
            'first_name' => 'string|nullable',
            'last_name' => 'string|nullable',
            'fathers_name' => 'string|nullable',
            'birth_date' => 'integer|nullable',
            'region_id' => 'integer|nullable',
            'city_id' => 'integer|nullable',
            'address' => 'string|nullable',
            'password' => 'string|nullable',
            'passport' => 'string|nullable',
            'tin' => 'integer|nullable',
            'remember_token' => 'string|nullable',
            'created_at' => 'datetime|nullable',
            'updated_at' => 'datetime|nullable',
        ];
    }

    public function region() {
        return $this->belongsTo('App\Region','region_id');
    }
    public function city() {
        return $this->belongsTo('App\City','city_id');
    }


    public function setBirthDateAttribute($value)
    {
        if (strpos($value, '.')) {
            $b_date = explode(".", $value);
            $value = $b_date[2] . "-" . $b_date[1] . "-" . $b_date[0];
        }
        $this->attributes['birth_date'] = $value;
    }

    public function scopeFilter(Builder $query, $attributes)
    {
        return $query->when($attributes['region_id'] ?? null, function (Builder $query, $region_id) {
            return $query->where('citizens.region_id', '=', $region_id);
        })->when($attributes['city_id'] ?? null, function (Builder $query, $city_id) {
            return $query->where('citizens.city_id', '=', $city_id);
        })->when($attributes['surname'] ?? null, function (Builder $query, $surname) {
            return $query->where('citizens.surname', 'like', $surname.'%');
        })->when($attributes['firstname'] ?? null, function (Builder $query, $firstname) {
            return $query->where('citizens.firstname', '=', $firstname);
        })->when($attributes['patronymic'] ?? null, function (Builder $query, $patronymic) {
            return $query->where('citizens.patronymic', '=', $patronymic);
        })->when($attributes['passport'] ?? null, function (Builder $query, $passport) {
            return $query->where('citizens.passport', '=', $passport);
        })->when($attributes['pin'] ?? null, function (Builder $query, $pin) {
            return $query->where('citizens.pin', '=', $pin);
        })->when($attributes['living_place'] ?? null, function (Builder $query, $living_place) {
            return $query->where('citizens.living_place', '=', $living_place);
        });
    }
}
