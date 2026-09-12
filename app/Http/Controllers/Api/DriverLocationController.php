<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDriverAvailabilityRequest;
use App\Http\Requests\UpdateDriverLocationRequest;
use App\Models\DriverLocation;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
    /**
     * Conductores disponibles cerca de un punto (para mostrarlos como
     * mototaxis en el mapa del ciudadano, antes de pedir un viaje).
     * Solo expone lat/lng — nada de datos personales del conductor.
     */
    public function nearby(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.5|max:20',
        ]);

        $drivers = DriverLocation::near($data['lat'], $data['lng'], $data['radius_km'] ?? 5)
            ->whereHas('driverProfile', function ($q) {
                $q->where('approval_status', 'approved')
                    ->where('availability_status', 'available');
            })
            ->get(['driver_profile_id', 'latitude', 'longitude', 'heading']);

        return response()->json(
            $drivers->map(fn ($d) => [
                'id' => $d->driver_profile_id,
                'lat' => (float) $d->latitude,
                'lng' => (float) $d->longitude,
                'heading' => $d->heading,
            ])
        );
    }

    /**
     * El conductor envía su ubicación periódicamente (polling o websocket)
     * mientras está en línea. Se actualiza (updateOrCreate) en vez de
     * insertar una fila nueva cada vez.
     */
    public function update(UpdateDriverLocationRequest $request)
    {
        $data = $request->validated();

        $driverProfile = $request->user()->driverProfile;

        $location = $driverProfile->location()->updateOrCreate(
            ['driver_profile_id' => $driverProfile->id],
            [...$data, 'updated_at_gps' => now()]
        );

        // Si el conductor tiene un viaje activo, avisamos al ciudadano de
        // ESE viaje en tiempo real (para que vea el mototaxi moverse en su mapa).
        $activeAssignment = $driverProfile->tripAssignments()
            ->whereNotIn('acceptance_status', ['finished', 'cancelled'])
            ->latest()
            ->first();

        if ($activeAssignment) {
            broadcast(new DriverLocationUpdated(
                $activeAssignment,
                (float) $data['latitude'],
                (float) $data['longitude'],
                $data['heading'] ?? null,
            ))->toOthers();
        }

        return response()->json($location);
    }

    /**
     * El conductor cambia su disponibilidad: available / offline.
     * (busy se controla desde el flujo de aceptación/finalización de viaje).
     */
    public function updateAvailability(UpdateDriverAvailabilityRequest $request)
    {
        $data = $request->validated();

        $driverProfile = $request->user()->driverProfile;
        $driverProfile->update($data);

        if ($data['availability_status'] === 'available' && ! $driverProfile->currentShift) {
            $driverProfile->shifts()->create(['started_at' => now()]);
        }

        if ($data['availability_status'] === 'offline' && $driverProfile->currentShift) {
            $driverProfile->currentShift->update(['ended_at' => now()]);
        }

        return response()->json($driverProfile->fresh());
    }
}
