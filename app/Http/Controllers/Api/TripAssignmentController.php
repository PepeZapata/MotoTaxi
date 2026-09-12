<?php

namespace App\Http\Controllers\Api;

use App\Events\TripAssignmentAccepted;
use App\Events\TripStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTripStatusRequest;
use App\Models\ServiceRequest;
use App\Models\TripAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripAssignmentController extends Controller
{
    /**
     * Un conductor acepta una solicitud abierta.
     *
     * CRÍTICO: esto debe ser atómico. Usamos una transacción con
     * lockForUpdate() para que, si dos conductores presionan "aceptar"
     * casi al mismo tiempo, solo el primero gane y el segundo reciba
     * un error claro en lugar de crear dos asignaciones para el mismo viaje.
     */
    public function accept(Request $request, ServiceRequest $serviceRequest)
    {
        $driverProfile = $request->user()->driverProfile;

        if (! $driverProfile || ! $driverProfile->isApproved()) {
            return response()->json([
                'message' => 'Tu cuenta de conductor no está aprobada.',
            ], 403);
        }

        if (! $driverProfile->isAvailable()) {
            return response()->json([
                'message' => 'Debes estar disponible para aceptar viajes.',
            ], 422);
        }

        $assignment = DB::transaction(function () use ($serviceRequest, $driverProfile, $request) {
            // Bloqueamos la fila de la solicitud hasta que termine la transacción.
            $locked = ServiceRequest::where('id', $serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'open') {
                // Otro conductor ya la tomó (o fue cancelada) entre que se listó y se aceptó.
                abort(409, 'Esta solicitud ya no está disponible.');
            }

            $locked->update(['status' => 'assigned']);

            $assignment = TripAssignment::create([
                'service_request_id' => $locked->id,
                'driver_profile_id' => $driverProfile->id,
                'vehicle_id' => optional($driverProfile->activeVehicle)->id,
                'acceptance_status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $locked->statusLogs()->create([
                'status' => 'accepted',
                'changed_by' => $request->user()->id,
            ]);

            $driverProfile->update(['availability_status' => 'busy']);

            return $assignment;
        });

        broadcast(new TripAssignmentAccepted($assignment))->toOthers();

        return response()->json($assignment, 201);
    }

    /**
     * Actualiza el progreso del viaje: en camino, llegó, inició, terminó.
     */
    public function updateStatus(UpdateTripStatusRequest $request, TripAssignment $tripAssignment)
    {
        $data = $request->validated();

        $timestampColumn = match ($data['acceptance_status']) {
            'arrived' => 'arrived_at',
            'started' => 'started_at',
            'finished' => 'finished_at',
            default => null,
        };

        DB::transaction(function () use ($tripAssignment, $data, $timestampColumn, $request) {
            $tripAssignment->update([
                'acceptance_status' => $data['acceptance_status'],
                ...($timestampColumn ? [$timestampColumn => now()] : []),
            ]);

            $tripAssignment->serviceRequest->statusLogs()->create([
                'status' => $data['acceptance_status'] === 'finished' ? 'finished' : $data['acceptance_status'],
                'changed_by' => $request->user()->id,
            ]);

            if ($data['acceptance_status'] === 'finished') {
                $tripAssignment->serviceRequest->update(['status' => 'completed']);
                $tripAssignment->driverProfile->update(['availability_status' => 'available']);
            }

            if ($data['acceptance_status'] === 'started') {
                $tripAssignment->serviceRequest->update(['status' => 'in_progress']);
            }
        });

        broadcast(new TripStatusUpdated($tripAssignment->fresh()))->toOthers();

        return response()->json($tripAssignment->fresh());
    }
}
