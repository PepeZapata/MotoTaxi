<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();

            // Unique: garantiza a nivel de base de datos que una solicitud
            // solo puede tener UNA asignación (un solo conductor la toma).
            $table->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('acceptance_status', [
                'accepted',
                'en_route_to_pickup',
                'arrived',
                'started',
                'finished',
                'cancelled',
            ])->default('accepted');

            $table->timestamp('accepted_at')->useCurrent();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
