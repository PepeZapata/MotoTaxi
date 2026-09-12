<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverDecisionRequest;
use App\Models\DriverProfile;
use Illuminate\Http\Request;

class DriverApprovalController extends Controller
{
    /**
     * Lista de conductores pendientes de aprobación (para el panel admin).
     */
    public function pending(Request $request)
    {
        $drivers = DriverProfile::with('user', 'vehicles')
            ->where('approval_status', 'pending')
            ->get();

        return response()->json($drivers);
    }

    /**
     * Lista de TODOS los conductores (para la pestaña "todos" del panel admin).
     */
    public function all(Request $request)
    {
        $drivers = DriverProfile::with('user', 'vehicles')
            ->latest()
            ->get();

        return response()->json($drivers);
    }

    /**
     * Admin aprueba o rechaza a un conductor.
     */
    public function decide(DriverDecisionRequest $request, DriverProfile $driverProfile)
    {
        $data = $request->validated();

        $driverProfile->update([
            'approval_status' => $data['decision'],
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return response()->json($driverProfile->fresh());
    }
}
