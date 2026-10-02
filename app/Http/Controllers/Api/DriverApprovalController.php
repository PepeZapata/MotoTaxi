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
     *
     * Para poder aprobar la cuenta completa, TODOS los documentos
     * requeridos deben estar ya aprobados individualmente y vigentes (no
     * vencidos) — así no se puede dar de alta a un conductor al que le
     * falte, por ejemplo, el seguro. Rechazar la cuenta no tiene esta
     * restricción (siempre se puede rechazar).
     */
    public function decide(DriverDecisionRequest $request, DriverProfile $driverProfile)
    {
        $data = $request->validated();

        if ($data['decision'] === 'approved' && ! $driverProfile->hasAllDocumentsValid()) {
            return response()->json([
                'message' => 'No se puede aprobar: faltan documentos por subir, aprobar, o están vencidos.',
                'missing_documents' => $driverProfile->missingOrInvalidDocumentLabels(),
            ], 422);
        }

        $driverProfile->update([
            'approval_status' => $data['decision'],
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return response()->json($driverProfile->fresh());
    }
}
