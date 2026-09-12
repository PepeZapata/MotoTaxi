<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'service_request_id',
        'driver_profile_id',
        'method',
        'amount',
        'status',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    public function isCollected(): bool
    {
        return $this->status === 'collected';
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
}
