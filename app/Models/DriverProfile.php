<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    protected $fillable = [
        'user_id',
        'license_number',
        'license_expires_at',
        'approval_status',
        'approved_at',
        'approved_by',
        'availability_status',
        'rating_avg',
    ];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isAvailable(): bool
    {
        return $this->availability_status === 'available';
    }

    // --- Relaciones ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function activeVehicle()
    {
        return $this->hasOne(Vehicle::class)->where('is_active', true);
    }

    public function location()
    {
        return $this->hasOne(DriverLocation::class);
    }

    public function tripAssignments()
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    // Turno abierto actual (sin ended_at), si existe
    public function currentShift()
    {
        return $this->hasOne(Shift::class)->whereNull('ended_at')->latestOfMany();
    }

    public function documents()
    {
        return $this->hasMany(DriverDocument::class);
    }

    /**
     * Para cada tipo de documento del catálogo (DriverDocument::TYPES),
     * regresa el más reciente que haya subido este conductor (o null si
     * nunca ha subido uno de ese tipo). Es lo que usamos tanto para la
     * pantalla de documentos del conductor como para la del admin: "lo
     * último que subió" es lo único que importa, aunque haya historial de
     * intentos rechazados antes.
     *
     * @return array<string, DriverDocument|null>
     */
    public function latestDocumentsByType(): array
    {
        $latestById = $this->documents()
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('type')
            ->map(fn ($docs) => $docs->first());

        $result = [];
        foreach (array_keys(DriverDocument::TYPES) as $type) {
            $result[$type] = $latestById->get($type);
        }

        return $result;
    }

    /**
     * true solo si TODOS los documentos requeridos están aprobados y
     * vigentes (no vencidos). La usa el admin antes de poder marcar la
     * cuenta completa como "approved".
     */
    public function hasAllDocumentsValid(): bool
    {
        foreach ($this->latestDocumentsByType() as $doc) {
            if (! $doc || ! $doc->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tipos de documento que faltan por subir o que están pendientes/
     * rechazados/vencidos (para mostrarle al admin por qué no puede
     * aprobar la cuenta todavía).
     *
     * @return array<int, string> labels legibles
     */
    public function missingOrInvalidDocumentLabels(): array
    {
        $labels = [];
        foreach ($this->latestDocumentsByType() as $type => $doc) {
            if (! $doc || ! $doc->isValid()) {
                $labels[] = DriverDocument::labelFor($type);
            }
        }

        return $labels;
    }
}
