<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Geohash;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    /**
     * Dado un lat/lng, devuelve la celda geohash correspondiente y sus
     * vecinas. El cliente (app del conductor) llama esto cada vez que su
     * ubicación cambia lo suficiente como para haber cruzado de celda, y
     * usa el resultado para suscribirse a los canales "drivers.zone.{celda}"
     * correctos — así no tiene que reimplementar el algoritmo de geohash.
     */
    public function zone(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $cell = Geohash::encode($data['lat'], $data['lng']);

        return response()->json([
            'cell' => $cell,
            'channels' => array_map(
                fn (string $c) => "drivers.zone.{$c}",
                Geohash::withNeighbors($cell)
            ),
        ]);
    }
}
