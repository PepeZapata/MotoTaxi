<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    protected $fillable = [
        'user_id',
        'license_number',
        'license_expires_at',
        'approval_status',
        'approved_at',
        'approved_by',
        'availability_status',
        'rating_avg',
    ];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isAvailable(): bool
    {
        return $this->availability_status === 'available';
    }

    // --- Relaciones ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function activeVehicle()
    {
        return $this->hasOne(Vehicle::class)->where('is_active', true);
    }

    public function location()
    {
        return $this->hasOne(DriverLocation::class);
    }

    public function tripAssignments()
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    // Turno abierto actual (sin ended_at), si existe
    public function currentShift()
    {
        return $this->hasOne(Shift::class)->whereNull('ended_at')->latestOfMany();
    }
}
