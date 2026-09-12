<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDriverAvailabilityRequest;
use App\Http\Requests\UpdateDriverLocationRequest;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
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
