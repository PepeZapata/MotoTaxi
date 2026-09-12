<?php

namespace App\Models;

use App\Support\Geohash;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'citizen_profile_id',
        'origin_lat',
        'origin_lng',
        'origin_address',
        'origin_geohash',
        'destination_lat',
        'destination_lng',
        'destination_address',
        'estimated_distance_km',
        'estimated_cost',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (ServiceRequest $serviceRequest) {
            if ($serviceRequest->isDirty(['origin_lat', 'origin_lng']) || ! $serviceRequest->origin_geohash) {
                $serviceRequest->origin_geohash = Geohash::encode(
                    (float) $serviceRequest->origin_lat,
                    (float) $serviceRequest->origin_lng
                );
            }
        });
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    // --- Relaciones ---

    public function citizenProfile()
    {
        return $this->belongsTo(CitizenProfile::class);
    }

    public function assignment()
    {
        return $this->hasOne(TripAssignment::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(TripStatusLog::class)->orderBy('logged_at');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function cancellation()
    {
        return $this->hasOne(Cancellation::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
