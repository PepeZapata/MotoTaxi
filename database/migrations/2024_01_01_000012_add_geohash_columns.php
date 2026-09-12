<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Celda geográfica del punto de origen (ver app/Support/Geohash.php).
            // Se usa para saber a qué canal(es) transmitir la solicitud nueva.
            $table->string('origin_geohash', 12)->nullable()->after('origin_address');
            $table->index('origin_geohash');
        });

        Schema::table('driver_locations', function (Blueprint $table) {
            // Celda geográfica de la última ubicación del conductor.
            // Se usa para filtrar "conductores cercanos" sin tener que
            // calcular Haversine sobre TODA la tabla en cada búsqueda.
            $table->string('geohash', 12)->nullable()->after('longitude');
            $table->index('geohash');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['origin_geohash']);
            $table->dropColumn('origin_geohash');
        });

        Schema::table('driver_locations', function (Blueprint $table) {
            $table->dropIndex(['geohash']);
            $table->dropColumn('geohash');
        });
    }
};
