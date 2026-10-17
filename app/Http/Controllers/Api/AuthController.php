<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Iniciar sesión de administrador y generar token de Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        if (! $user->is_admin) {
            return response()->json([
                'message' => 'Acceso denegado. Esta cuenta no tiene permisos de administrador.',
            ], 403);
        }

        // Crear token de acceso personal
        $token = $user->createToken('admin-api-token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'tokenType' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => (bool) $user->is_admin,
            ],
        ], 200);
    }

    /**
     * Cerrar sesión y revocar el token actual del usuario autenticado.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente. Token revocado.',
        ], 200);
    }

    /**
     * Obtener el perfil del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => (bool) $user->is_admin,
            ],
        ], 200);
    }
}
