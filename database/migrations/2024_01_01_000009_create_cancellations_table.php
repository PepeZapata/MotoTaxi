<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete();

            // Quién canceló: ciudadano, conductor o administrador
            $table->foreignId('cancelled_by')->constrained('users')->cascadeOnDelete();
            $table->enum('cancelled_by_role', ['citizen', 'driver', 'admin']);

            $table->string('reason')->nullable();
            $table->timestamp('cancelled_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
