<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();

            // Siempre ligado al conductor (para poder listar "todos los
            // documentos de este conductor" con una sola consulta); además,
            // para los documentos que son del vehículo (tarjeta de
            // circulación, póliza, etc.) también guardamos a qué vehículo
            // corresponden, pensando en el día que un conductor pueda tener
            // más de un vehículo.
            $table->foreignId('driver_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

            // Uno de App\Models\DriverDocument::TYPES (license, official_id,
            // no_criminal_record, proof_of_address, vehicle_permit,
            // vehicle_certificate, circulation_card, insurance_policy).
            $table->string('type');

            // No sobreescribimos el archivo anterior al resubir: cada subida
            // crea un renglón nuevo (vuelve a quedar "pending"), así se
            // conserva el historial de intentos/rechazos. El más reciente de
            // cada "type" es el que cuenta como vigente.
            $table->string('disk'); // qué disco de config/filesystems.php se usó (ver driver_documents_disk)
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();

            // No todos los documentos tienen fecha de vencimiento explícita
            // (ej. comprobante de domicilio), por eso es opcional.
            $table->date('expires_at')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['driver_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};
