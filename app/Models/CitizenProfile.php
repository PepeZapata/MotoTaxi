<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenProfile extends Model
{
    protected $fillable = [
        'user_id',
        'preferred_name',
        'emergency_contact_phone',
        'rating_avg',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
