<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripAssignment extends Model
{
    protected $fillable = [
        'service_request_id',
        'driver_profile_id',
        'vehicle_id',
        'acceptance_status',
        'accepted_at',
        'arrived_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'arrived_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
