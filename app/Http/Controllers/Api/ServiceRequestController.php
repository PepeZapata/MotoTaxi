<?php

namespace App\Http\Controllers\Api;

use App\Events\ServiceRequestCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Support\Geohash;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * Ciudadano crea una nueva solicitud de viaje.
     * Al crearla queda en status "open" y visible para conductores cercanos.
     */
    public function store(StoreServiceRequestRequest $request)
    {
        $data = $request->validated();

        $citizenProfile = $request->user()->citizenProfile;

        $serviceRequest = ServiceRequest::create([
            ...$data,
            'citizen_profile_id' => $citizenProfile->id,
            'status' => 'open',
        ]);

        $serviceRequest->statusLogs()->create([
            'status' => 'requested',
            'changed_by' => $request->user()->id,
        ]);

        broadcast(new ServiceRequestCreated($serviceRequest))->toOthers();

        return response()->json($serviceRequest, 201);
    }

    /**
     * Conductor consulta solicitudes abiertas (pendientes de asignar).
     *
     * Si el conductor manda ?lat=..&lng=.., filtramos solo las solicitudes
     * cuyo origen cae en su celda geohash actual o en las 8 vecinas —
     * esto es lo que hace que el sistema escale: un conductor en Cancún
     * nunca ve, ni siquiera al hacer polling, las solicitudes de Mérida.
     * Sin lat/lng, devuelve todas (comportamiento anterior, por compatibilidad).
     */
    public function openRequests(Request $request)
    {
        $query = ServiceRequest::where('status', 'open');

        if ($request->filled('lat') && $request->filled('lng')) {
            $data = $request->validate([
                'lat' => 'numeric|between:-90,90',
                'lng' => 'numeric|between:-180,180',
            ]);

            $cell = Geohash::encode($data['lat'], $data['lng']);
            $cells = Geohash::withNeighbors($cell);

            $query->whereIn('origin_geohash', $cells);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Ciudadano consulta el estado de su propia solicitud.
     */
    public function show(Request $request, ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['assignment.driverProfile.user', 'statusLogs']);

        return response()->json($serviceRequest);
    }
}
