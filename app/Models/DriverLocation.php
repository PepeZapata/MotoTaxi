<?php

namespace App\Models;

use App\Support\Geohash;
use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_profile_id',
        'latitude',
        'longitude',
        'heading',
        'geohash',
        'updated_at_gps',
    ];

    protected static function booted(): void
    {
        // Cada vez que se guarda una ubicación, recalculamos su geohash
        // automáticamente — así nunca queda desincronizado con lat/lng.
        static::saving(function (DriverLocation $location) {
            if ($location->isDirty(['latitude', 'longitude']) || ! $location->geohash) {
                $location->geohash = Geohash::encode(
                    (float) $location->latitude,
                    (float) $location->longitude
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'updated_at_gps' => 'datetime',
        ];
    }

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class);
    }

    /**
     * Scope que filtra conductores dentro de las celdas geohash indicadas
     * (rápido, usa índice) y luego ordena por distancia exacta con Haversine
     * solo sobre ese subconjunto ya reducido.
     */
    public function scopeNear($query, float $lat, float $lng, float $radiusKm = 3)
    {
        $cell = Geohash::encode($lat, $lng);
        $cells = Geohash::withNeighbors($cell);

        $haversine = "(6371 * acos(cos(radians($lat))
            * cos(radians(latitude))
            * cos(radians(longitude) - radians($lng))
            + sin(radians($lat))
            * sin(radians(latitude))))";

        return $query
            ->whereIn('geohash', $cells)
            ->selectRaw("driver_locations.*, {$haversine} AS distance_km")
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }
}

