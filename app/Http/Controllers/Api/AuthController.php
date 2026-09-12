<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterCitizenRequest;
use App\Http\Requests\RegisterDriverRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registro de ciudadano. Crea el user + su citizen_profile.
     */
    public function registerCitizen(RegisterCitizenRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'citizen',
        ]);

        $user->citizenProfile()->create([]);

        $token = $user->createToken('citizen-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Registro de conductor. Crea el user + driver_profile en estado "pending"
     * (queda inactivo hasta que un admin lo apruebe desde /admin/drivers/pending).
     */
    public function registerDriver(RegisterDriverRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'driver',
        ]);

        $user->driverProfile()->create([
            'license_number' => $data['license_number'],
            'approval_status' => 'pending',
            'availability_status' => 'offline',
        ]);

        $token = $user->createToken('driver-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registro exitoso. Tu cuenta debe ser aprobada por un administrador antes de poder recibir viajes.',
        ], 201);
    }

    /**
     * Login para cualquier rol. Devuelve el token que se usa en
     * 'Authorization: Bearer {token}' en el resto de las rutas.
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no coinciden con nuestros registros.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta está desactivada.'],
            ]);
        }

        $token = $user->createToken($user->role.'-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout: revoca solo el token actual (así no cierra sesión en otros dispositivos).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Devuelve el usuario autenticado con su perfil correspondiente.
     * Útil para que el frontend sepa qué pantalla mostrar al abrir la app.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load($user->isDriver() ? 'driverProfile' : 'citizenProfile');

        return response()->json($user);
    }
}
