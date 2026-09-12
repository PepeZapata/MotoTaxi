<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained()->cascadeOnDelete();

            // Por ahora solo efectivo; se deja abierto por si luego se agregan pasarelas.
            $table->enum('method', ['cash'])->default('cash');

            $table->decimal('amount', 8, 2);
            $table->enum('status', ['pending', 'collected', 'failed'])->default('pending');
            $table->timestamp('collected_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
