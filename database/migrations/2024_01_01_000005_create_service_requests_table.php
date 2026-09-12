<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_profile_id')->constrained()->cascadeOnDelete();

            // Origen
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);
            $table->string('origin_address')->nullable();

            // Destino
            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);
            $table->string('destination_address')->nullable();

            $table->decimal('estimated_distance_km', 8, 2)->nullable();
            $table->decimal('estimated_cost', 8, 2)->nullable();

            // Estado general de la solicitud. "open" = visible para conductores cercanos.
            $table->enum('status', [
                'open',       // esperando que un conductor acepte
                'assigned',   // ya tiene conductor asignado
                'in_progress',
                'completed',
                'cancelled',
            ])->default('open');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
