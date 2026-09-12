<?php

namespace App\Events;

use App\Models\TripAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cuando un conductor acepta una solicitud.
 * El ciudadano, escuchando su propio canal privado, ve al instante
 * quién va a recogerlo sin necesidad de refrescar/pollear.
 */
class TripAssignmentAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TripAssignment $tripAssignment)
    {
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
        return 'trip-assignment.accepted';
    }

    public function broadcastWith(): array
    {
        $driver = $this->tripAssignment->driverProfile->load('user', 'activeVehicle');

        return [
            'service_request_id' => $this->tripAssignment->service_request_id,
            'driver_name' => $driver->user->name,
            'driver_phone' => $driver->user->phone,
            'vehicle_plate' => optional($driver->activeVehicle)->plate,
            'vehicle_model' => optional($driver->activeVehicle)->model,
        ];
    }
}
