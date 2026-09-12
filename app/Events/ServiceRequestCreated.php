<?php

namespace App\Events;

use App\Models\ServiceRequest;
use App\Support\Geohash;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cuando un ciudadano crea una solicitud nueva.
 *
 * En vez de transmitir a un único canal global "drivers.available" (que no
 * escala: un conductor en Cancún vería solicitudes de Mérida), transmitimos
 * SOLO a las celdas geohash cercanas al origen del viaje: la celda donde
 * cayó la solicitud, más sus 8 celdas vecinas (para cubrir conductores
 * justo en el borde de una celda). Cada conductor, del lado del cliente,
 * calcula su propia celda actual (según su ubicación GPS) y se suscribe
 * a "drivers.zone.{esa_celda}" — así solo escucha lo relevante para él.
 */
class ServiceRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceRequest $serviceRequest)
    {
    }

    public function broadcastOn(): array
    {
        $cells = Geohash::withNeighbors($this->serviceRequest->origin_geohash);

        return array_map(
            fn (string $cell) => new PrivateChannel("drivers.zone.{$cell}"),
            $cells
        );
    }

    public function broadcastAs(): string
    {
        return 'service-request.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->serviceRequest->id,
            'origin_lat' => $this->serviceRequest->origin_lat,
            'origin_lng' => $this->serviceRequest->origin_lng,
            'origin_address' => $this->serviceRequest->origin_address,
            'destination_address' => $this->serviceRequest->destination_address,
            'estimated_cost' => $this->serviceRequest->estimated_cost,
        ];
    }
}

