<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DriverDocument extends Model
{
    protected $fillable = [
        'driver_profile_id',
        'vehicle_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'expires_at',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const OWNER_DRIVER = 'driver';
    public const OWNER_VEHICLE = 'vehicle';

    /**
     * Catálogo de documentos que le pedimos a un conductor. "owner" indica
     * si el documento pertenece al conductor como persona o al vehículo;
     * "requires_expiry" marca los que SIEMPRE deben traer fecha de
     * vencimiento para poder aprobarse (licencia y póliza de seguro: son
     * los dos que por ley se renuevan con fecha fija y donde operar con uno
     * vencido es el riesgo más directo). Para el resto, la fecha de
     * vencimiento es opcional: el admin puede ver el documento y decidir.
     */
    public const TYPES = [
        'license' => [
            'label' => 'Licencia de conducir vigente',
            'owner' => self::OWNER_DRIVER,
            'requires_expiry' => true,
        ],
        'official_id' => [
            'label' => 'Identificación oficial vigente',
            'owner' => self::OWNER_DRIVER,
            'requires_expiry' => false,
        ],
        'no_criminal_record' => [
            'label' => 'Constancia de no antecedentes penales',
            'owner' => self::OWNER_DRIVER,
            'requires_expiry' => false,
        ],
        'proof_of_address' => [
            'label' => 'Comprobante de domicilio',
            'owner' => self::OWNER_DRIVER,
            'requires_expiry' => false,
        ],
        'vehicle_permit' => [
            'label' => 'Permiso de concesión o circulación especial (Gobierno del Estado)',
            'owner' => self::OWNER_VEHICLE,
            'requires_expiry' => false,
        ],
        'vehicle_certificate' => [
            'label' => 'Constancia y certificado vehicular',
            'owner' => self::OWNER_VEHICLE,
            'requires_expiry' => false,
        ],
        'circulation_card' => [
            'label' => 'Tarjeta de circulación vigente y placas visibles',
            'owner' => self::OWNER_VEHICLE,
            'requires_expiry' => true,
        ],
        'insurance_policy' => [
            'label' => 'Póliza de seguro de responsabilidad civil',
            'owner' => self::OWNER_VEHICLE,
            'requires_expiry' => true,
        ],
    ];

    public static function labelFor(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    public static function ownerFor(string $type): ?string
    {
        return self::TYPES[$type]['owner'] ?? null;
    }

    public static function requiresExpiryFor(string $type): bool
    {
        return self::TYPES[$type]['requires_expiry'] ?? false;
    }

    // --- Relaciones ---

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // --- Estado ---

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * "Vigente" para efectos de aprobación de la cuenta: aprobado por el
     * admin Y, si tiene fecha de vencimiento, que todavía no haya pasado.
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_APPROVED && ! $this->isExpired();
    }

    /**
     * Estatus "visible" para la UI: igual que `status`, salvo que un
     * documento aprobado pero ya vencido se muestra como "expired" en vez
     * de "approved", sin necesidad de un job que actualice la columna.
     */
    public function displayStatus(): string
    {
        if ($this->status === self::STATUS_APPROVED && $this->isExpired()) {
            return 'expired';
        }

        return $this->status;
    }

    public function url(): ?string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl(
                $this->path,
                now()->addMinutes(10)
            );
        } catch (\Throwable $e) {
            // El disco 'local' (privado) no soporta temporaryUrl; en ese
            // caso se sirve a través de nuestro propio endpoint autenticado
            // (ver DriverDocumentController::file), no de una URL directa.
            return null;
        }
    }
}
