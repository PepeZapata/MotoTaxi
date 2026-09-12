<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'driver_profile_id',
        'started_at',
        'ended_at',
        'total_collected',
        'total_trips',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'total_collected' => 'decimal:2',
        ];
    }

    public function isOpen(): bool
    {
        return is_null($this->ended_at);
    }

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class);
    }
}
