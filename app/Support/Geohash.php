<?php

namespace App\Support;

/**
 * Implementación simple del algoritmo estándar de Geohash.
 *
 * Se usa para dividir el mapa en celdas rectangulares y así poder
 * transmitir eventos ("hay una solicitud nueva") solo a los conductores
 * que están en la celda relevante, en vez de a todos los conductores
 * de la ciudad/país en un único canal.
 *
 * Precisión usada en este proyecto: 5 caracteres ≈ celdas de ~4.9km x 4.9km,
 * un tamaño razonable para una ciudad como Mérida. Si el área de cobertura
 * crece mucho, se puede subir a 6 caracteres (~1.2km x 0.6km) para celdas
 * más finas.
 */
class Geohash
{
    private const BASE32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    public const PRECISION = 5;

    /**
     * Convierte lat/lng en un código de celda, ej: "9g6d5"
     */
    public static function encode(float $lat, float $lng, int $precision = self::PRECISION): string
    {
        $latRange = [-90.0, 90.0];
        $lngRange = [-180.0, 180.0];

        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;

        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lngRange[0] + $lngRange[1]) / 2;
                if ($lng >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $lngRange[0] = $mid;
                } else {
                    $lngRange[1] = $mid;
                }
            } else {
                $mid = ($latRange[0] + $latRange[1]) / 2;
                if ($lat >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }

            $isEven = ! $isEven;

            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= self::BASE32[$ch];
                $bit = 0;
                $ch = 0;
            }
        }

        return $geohash;
    }

    /**
     * Devuelve la celda dada más sus 8 celdas vecinas (arriba, abajo,
     * izquierda, derecha y las 4 diagonales). Total: 9 códigos.
     *
     * Esto es clave para que un conductor que está justo en el borde
     * de su celda no se pierda una solicitud que cayó en la celda de al lado.
     */
    public static function withNeighbors(string $geohash): array
    {
        [$lat, $lng, $latErr, $lngErr] = self::decodeWithError($geohash);
        $precision = strlen($geohash);

        $cells = [$geohash];

        for ($dLat = -1; $dLat <= 1; $dLat++) {
            for ($dLng = -1; $dLng <= 1; $dLng++) {
                if ($dLat === 0 && $dLng === 0) {
                    continue;
                }

                $neighborLat = $lat + ($dLat * $latErr * 2);
                $neighborLng = $lng + ($dLng * $lngErr * 2);

                // El mundo es un rectángulo: no nos salimos de rango.
                $neighborLat = max(-90, min(90, $neighborLat));
                $neighborLng = max(-180, min(180, $neighborLng));

                $cells[] = self::encode($neighborLat, $neighborLng, $precision);
            }
        }

        return array_values(array_unique($cells));
    }

    /**
     * Decodifica un geohash devolviendo el centro de la celda y el margen
     * de error (mitad del ancho/alto de la celda) en lat/lng.
     */
    private static function decodeWithError(string $geohash): array
    {
        $latRange = [-90.0, 90.0];
        $lngRange = [-180.0, 180.0];
        $isEven = true;

        foreach (str_split($geohash) as $char) {
            $idx = strpos(self::BASE32, $char);

            for ($i = 4; $i >= 0; $i--) {
                $bit = ($idx >> $i) & 1;

                if ($isEven) {
                    $mid = ($lngRange[0] + $lngRange[1]) / 2;
                    if ($bit) {
                        $lngRange[0] = $mid;
                    } else {
                        $lngRange[1] = $mid;
                    }
                } else {
                    $mid = ($latRange[0] + $latRange[1]) / 2;
                    if ($bit) {
                        $latRange[0] = $mid;
                    } else {
                        $latRange[1] = $mid;
                    }
                }

                $isEven = ! $isEven;
            }
        }

        $lat = ($latRange[0] + $latRange[1]) / 2;
        $lng = ($lngRange[0] + $lngRange[1]) / 2;
        $latErr = ($latRange[1] - $latRange[0]) / 2;
        $lngErr = ($lngRange[1] - $lngRange[0]) / 2;

        return [$lat, $lng, $latErr, $lngErr];
    }
}
