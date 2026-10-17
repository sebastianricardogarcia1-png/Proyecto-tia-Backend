<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImageUploadApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Autenticar como administrador para pruebas protegidas.
     */
    protected function authenticateAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        return $admin;
    }

    /**
     * Test: Petición sin autenticación es rechazada con 401
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('zapato.webp', 100, 'image/webp');

        $response = $this->postJson('/api/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Usuario normal (no admin) es rechazado con 403
     */
    public function test_non_admin_user_is_forbidden(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('zapato.webp', 100, 'image/webp');

        $response = $this->postJson('/api/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Administrador puede subir exitosamente un archivo WebP
     */
    public function test_admin_can_upload_valid_webp_image(): void
    {
        Storage::fake('public');
        $this->authenticateAsAdmin();

        $file = UploadedFile::fake()->create('zapato.webp', 100, 'image/webp');

        $response = $this->postJson('/api/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'url',
                'path',
                'fileName',
            ])
            ->assertJsonPath('message', 'Imagen subida exitosamente.');

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('storage/productos/', $response->json('url'));
    }

    /**
     * Test: Administrador puede subir exitosamente una imagen WebP en formato Base64
     */
    public function test_admin_can_upload_base64_encoded_image(): void
    {
        Storage::fake('public');
        $this->authenticateAsAdmin();

        // Pixel transparente WebP en base64
        $base64Image = 'data:image/webp;base64,UklGRkAAAABXRUJQVlA4IDQAAADwAQCdASoBAAEAAQAcJaACdLoAAP7/2QAA';

        $response = $this->postJson('/api/upload-image', [
            'image' => $base64Image,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'url',
                'path',
                'fileName',
            ]);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Test: Archivo no permitido (ej. PDF) es rechazado con 422
     */
    public function test_invalid_file_extension_is_rejected(): void
    {
        Storage::fake('public');
        $this->authenticateAsAdmin();

        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /**
     * Test: Archivo que supera el tamaño máximo permitido (5MB) es rechazado con 422
     */
    public function test_file_exceeding_max_size_is_rejected(): void
    {
        Storage::fake('public');
        $this->authenticateAsAdmin();

        // Archivo de 6MB (6144 KB)
        $file = UploadedFile::fake()->create('foto_pesada.webp', 6144, 'image/webp');

        $response = $this->postJson('/api/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }
}
