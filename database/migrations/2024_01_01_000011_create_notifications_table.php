<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro propio de notificaciones de negocio (distinto del notifications
        // que genera "php artisan notifications:table" de Laravel, si luego se usa ese canal).
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type'); // ej: nueva_solicitud, viaje_aceptado, conductor_llego, viaje_cancelado
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
