<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test: Login exitoso con credenciales de administrador
     */
    public function test_admin_can_login_successfully_and_receive_token(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin_test@dulcechic.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin_test@dulcechic.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'tokenType',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'isAdmin',
                ],
            ])
            ->assertJsonPath('user.email', 'admin_test@dulcechic.com')
            ->assertJsonPath('user.isAdmin', true);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test: Login falla con contraseña incorrecta
     */
    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin_test2@dulcechic.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin_test2@dulcechic.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Credenciales incorrectas.');
    }

    /**
     * Test: Login falla si el correo no existe
     */
    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@dulcechic.com',
            'password' => 'any_password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Credenciales incorrectas.');
    }

    /**
     * Test: Usuario normal (no admin) no puede iniciar sesión como administrador
     */
    public function test_non_admin_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'regular_user@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'regular_user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Acceso denegado. Esta cuenta no tiene permisos de administrador.');
    }

    /**
     * Test: Validación de campos requeridos en login
     */
    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test: Consulta de perfil /api/me con token válido
     */
    public function test_authenticated_admin_can_access_me_endpoint(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.id', $admin->id)
            ->assertJsonPath('user.email', $admin->email)
            ->assertJsonPath('user.isAdmin', true);
    }

    /**
     * Test: Cerrar sesión revoca el token
     */
    public function test_logout_revokes_token(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin_logout@dulcechic.com',
            'password' => Hash::make('secret123'),
        ]);

        // Login para obtener un token real de Sanctum
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'admin_logout@dulcechic.com',
            'password' => 'secret123',
        ]);

        $token = $loginResponse->json('token');
        $this->assertNotEmpty($token);

        // Verificar que el token existe en la base de datos
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Logout enviando el token en la cabecera Authorization
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('message', 'Sesión cerrada correctamente. Token revocado.');

        // Verificar que el token fue eliminado de la base de datos
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Limpiar la caché de autenticación del contenedor de pruebas para simular una nueva petición HTTP
        $this->app['auth']->forgetGuards();

        // Intentar usar el token revocado debe fallar con 401
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $subsequentResponse->assertStatus(401);
    }
}
