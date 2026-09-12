<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Métricas rápidas para el panel del administrador.
     */
    public function stats(Request $request)
    {
        return response()->json([
            'drivers' => [
                'pending' => DriverProfile::where('approval_status', 'pending')->count(),
                'approved' => DriverProfile::where('approval_status', 'approved')->count(),
                'rejected' => DriverProfile::where('approval_status', 'rejected')->count(),
                'online_now' => DriverProfile::where('availability_status', 'available')->count(),
            ],
            'citizens_total' => User::where('role', 'citizen')->count(),
            'trips' => [
                'open' => ServiceRequest::where('status', 'open')->count(),
                'in_progress' => ServiceRequest::whereIn('status', ['assigned', 'in_progress'])->count(),
                'completed' => ServiceRequest::where('status', 'completed')->count(),
                'cancelled' => ServiceRequest::where('status', 'cancelled')->count(),
            ],
        ]);
    }
}
