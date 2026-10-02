<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverDocumentDecisionRequest;
use App\Http\Requests\StoreDriverDocumentRequest;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverDocumentController extends Controller
{
    /**
     * El conductor ve el catálogo completo de documentos requeridos y, para
     * cada uno, el estatus de lo último que subió (o null si nunca ha
     * subido ese tipo).
     */
    public function index(Request $request)
    {
        $driverProfile = $request->user()->driverProfile;

        return response()->json($this->catalogWithLatest($driverProfile));
    }

    /**
     * El conductor sube (o vuelve a subir, si se lo rechazaron o venció) la
     * foto/PDF de un documento. Cada subida crea un renglón nuevo en
     * estatus "pending": no se sobreescribe el anterior, así queda
     * historial de intentos.
     */
    public function store(StoreDriverDocumentRequest $request)
    {
        $data = $request->validated();
        $driverProfile = $request->user()->driverProfile;
        $disk = config('filesystems.driver_documents_disk');

        $file = $request->file('file');
        $path = $file->store('driver-documents/'.$driverProfile->id, $disk);

        $vehicleId = null;
        if (DriverDocument::ownerFor($data['type']) === DriverDocument::OWNER_VEHICLE) {
            $vehicleId = $driverProfile->activeVehicle?->id ?? $driverProfile->vehicles()->first()?->id;
        }

        $document = $driverProfile->documents()->create([
            'vehicle_id' => $vehicleId,
            'type' => $data['type'],
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'expires_at' => $data['expires_at'] ?? null,
            'status' => DriverDocument::STATUS_PENDING,
        ]);

        return response()->json($document, 201);
    }

    /**
     * Sirve el archivo de un documento (foto/PDF). Compartido entre el
     * conductor dueño del documento y un admin — por eso el chequeo de
     * permisos va inline en vez de en el middleware de la ruta, que agrupa
     * por rol único (driver/admin), no por "dueño o admin".
     */
    public function file(Request $request, DriverDocument $driverDocument)
    {
        $user = $request->user();
        $isOwner = $driverDocument->driverProfile?->user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => 'No tienes permiso para ver este documento.'], 403);
        }

        $disk = Storage::disk($driverDocument->disk);

        if (! $disk->exists($driverDocument->path)) {
            return response()->json(['message' => 'El archivo ya no está disponible.'], 404);
        }

        return $disk->response($driverDocument->path, $driverDocument->original_name);
    }

    /**
     * Admin: documentos de un conductor en particular (catálogo completo +
     * lo último subido de cada tipo), para la pantalla de revisión.
     */
    public function adminIndex(Request $request, DriverProfile $driverProfile)
    {
        return response()->json($this->catalogWithLatest($driverProfile));
    }

    /**
     * Admin aprueba o rechaza UN documento puntual (no la cuenta completa).
     */
    public function decide(DriverDocumentDecisionRequest $request, DriverDocument $driverDocument)
    {
        $data = $request->validated();

        $driverDocument->update([
            'status' => $data['decision'],
            'rejection_reason' => $data['decision'] === 'rejected' ? $data['reason'] : null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json($driverDocument->fresh());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogWithLatest(DriverProfile $driverProfile): array
    {
        $latest = $driverProfile->latestDocumentsByType();

        $catalog = [];
        foreach (DriverDocument::TYPES as $type => $meta) {
            $doc = $latest[$type];

            $catalog[] = [
                'type' => $type,
                'label' => $meta['label'],
                'owner' => $meta['owner'],
                'requires_expiry' => $meta['requires_expiry'],
                'document' => $doc ? [
                    'id' => $doc->id,
                    'status' => $doc->status,
                    'display_status' => $doc->displayStatus(),
                    'expires_at' => $doc->expires_at?->toDateString(),
                    'rejection_reason' => $doc->rejection_reason,
                    'original_name' => $doc->original_name,
                    'uploaded_at' => $doc->created_at->toIso8601String(),
                    'reviewed_at' => $doc->reviewed_at?->toIso8601String(),
                ] : null,
            ];
        }

        return $catalog;
    }
}
