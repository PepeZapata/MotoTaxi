<?php

namespace App\Events;

use App\Models\TripAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cada vez que un conductor actualiza su ubicación MIENTRAS
 * tiene un viaje activo asignado. Solo se lo mandamos al ciudadano dueño
 * de ESE viaje (no es un broadcast general como el de "drivers.zone.*") —
 * así el ciudadano ve el mototaxi moverse en su mapa en tiempo real,
 * desde que lo aceptan hasta que termina el viaje.
 */
class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TripAssignment $tripAssignment,
        public float $lat,
        public float $lng,
        public ?int $heading = null,
    ) {
    }

    public function broadcastOn(): array
    {
        $citizenProfileId = $this->tripAssignment->serviceRequest->citizen_profile_id;

        return [
            new PrivateChannel("citizen.{$citizenProfileId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver-location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'service_request_id' => $this->tripAssignment->service_request_id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'heading' => $this->heading,
        ];
    }
}
