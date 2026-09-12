<?php

namespace App\Events;

use App\Models\TripAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara en cada cambio de estado del viaje (en camino, llegó,
 * inició, terminó, cancelado). El ciudadano ve el progreso en vivo.
 */
class TripStatusUpdated implements ShouldBroadcast
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
        return 'trip-status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'service_request_id' => $this->tripAssignment->service_request_id,
            'status' => $this->tripAssignment->acceptance_status,
        ];
    }
}
