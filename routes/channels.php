<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de broadcasting
|--------------------------------------------------------------------------
|
| Cada callback decide si el usuario autenticado (via Sanctum) puede
| escuchar ese canal privado.
|
*/

// Por defecto, la ruta /broadcasting/auth usa el guard de sesión web.
// Como esta app es 100% API con tokens Bearer de Sanctum, hay que
// decirle explícitamente que use ese guard, si no, cualquier intento
// de suscripción a un canal privado devuelve 403 sin importar el token.
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Cada conductor aprobado y disponible puede escuchar CUALQUIER celda
// geohash (drivers.zone.{geohash}). No restringimos a "su" celda exacta
// porque el cliente decide a cuáles suscribirse según su ubicación real;
// esto solo verifica que sea un conductor válido, no un ciudadano colado.
Broadcast::channel('drivers.zone.{geohash}', function ($user, $geohash) {
    return $user->isDriver()
        && $user->driverProfile
        && $user->driverProfile->isApproved();
});

// Cada ciudadano solo puede escuchar el canal de SU PROPIO perfil,
// donde llegan TripAssignmentAccepted y TripStatusUpdated.
Broadcast::channel('citizen.{citizenProfileId}', function ($user, $citizenProfileId) {
    return $user->isCitizen()
        && $user->citizenProfile
        && (int) $user->citizenProfile->id === (int) $citizenProfileId;
});
