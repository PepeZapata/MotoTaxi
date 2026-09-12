<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'driver_profile_id',
        'plate',
        'model',
        'color',
        'year',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class);
    }
}
