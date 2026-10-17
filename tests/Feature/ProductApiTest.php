<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
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
     * Test: Listar productos es público (GET /api/productos)
     */
    public function test_can_list_products(): void
    {
        $response = $this->getJson('/api/productos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'audience',
                        'category',
                        'description',
                        'isNew',
                        'isFeatured',
                        'active',
                        'reference',
                        'price',
                        'imageUrl',
                        'available',
                        'createdAt',
                        'updatedAt',
                        'variants',
                    ]
                ]
            ]);
    }

    /**
     * Test: Peticiones no autenticadas son rechazadas con 401 en creación, edición y eliminación
     */
    public function test_unauthenticated_requests_are_rejected_with_401(): void
    {
        $this->postJson('/api/productos', [])->assertStatus(401);
        $this->putJson('/api/productos/1', [])->assertStatus(401);
        $this->deleteJson('/api/productos/1')->assertStatus(401);
    }

    /**
     * Test: Usuario autenticado sin rol administrador es rechazado con 403 Forbidden
     */
    public function test_non_admin_user_is_forbidden_with_403(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/productos', ['nombre' => 'Test'])->assertStatus(403);
        $this->putJson('/api/productos/1', ['nombre' => 'Test'])->assertStatus(403);
        $this->deleteJson('/api/productos/1')->assertStatus(403);
    }

    /**
     * Test: Crear producto con variantes (POST /api/productos) como administrador
     */
    public function test_can_create_product_with_variants_and_camelcase_format(): void
    {
        $this->authenticateAsAdmin();

        $payload = [
            'name' => 'Nike Air Force 1 Test',
            'audience' => 'mujer',
            'category' => 'zapatos',
            'description' => 'Silueta clásica de prueba',
            'isNew' => true,
            'isFeatured' => false,
            'active' => true,
            'reference' => 'DC-TEST-01',
            'variants' => [
                [
                    'color' => 'Blanco Puro',
                    'price' => 240000,
                    'imageUrl' => 'https://via.placeholder.com/300x300.png',
                    'available' => true,
                    'reference' => 'DC-TEST-01-W',
                    'order' => 0,
                ],
                [
                    'color' => 'Negro Stealth',
                    'price' => 250000,
                    'imageUrl' => 'https://via.placeholder.com/300x300.png',
                    'available' => false,
                    'reference' => 'DC-TEST-01-B',
                    'order' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/productos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Nike Air Force 1 Test')
            ->assertJsonPath('data.audience', 'mujer')
            ->assertJsonPath('data.category', 'zapatos')
            ->assertJsonPath('data.isNew', true)
            ->assertJsonPath('data.price', 240000)
            ->assertJsonPath('data.available', true)
            ->assertJsonCount(2, 'data.variants')
            ->assertJsonPath('data.variants.0.color', 'Blanco Puro')
            ->assertJsonPath('data.variants.1.color', 'Negro Stealth')
            ->assertJsonPath('data.variants.1.available', false);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Nike Air Force 1 Test',
            'referencia' => 'DC-TEST-01',
        ]);

        $this->assertDatabaseHas('variantes', [
            'color' => 'Blanco Puro',
            'precio' => 240000,
        ]);
    }

    /**
     * Test: Ver detalle de producto (GET /api/productos/{id})
     */
    public function test_can_show_single_product(): void
    {
        $product = Product::create([
            'nombre' => 'Camisa Lino Test',
            'publico' => 'hombre',
            'categoria' => 'camisas',
            'descripcion' => 'Camisa ligera',
            'es_nuevo' => false,
            'es_destacado' => true,
            'activo' => true,
            'referencia' => 'DC-CAM-01',
        ]);

        $variant = $product->variantes()->create([
            'color' => 'Beige',
            'precio' => 120000,
            'imagen_url' => 'https://via.placeholder.com/300x300.png',
            'disponible' => true,
            'referencia' => 'DC-CAM-01-BEI',
            'orden' => 0,
        ]);

        $response = $this->getJson("/api/productos/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Camisa Lino Test')
            ->assertJsonPath('data.price', 120000)
            ->assertJsonPath('data.variants.0.id', $variant->id);
    }

    /**
     * Test: Actualizar producto y sincronizar variantes (PUT /api/productos/{id})
     */
    public function test_can_update_product_and_sync_variants(): void
    {
        $this->authenticateAsAdmin();

        $product = Product::create([
            'nombre' => 'Zapato Original',
            'publico' => 'mujer',
            'categoria' => 'zapatos',
            'activo' => true,
        ]);

        $variant1 = $product->variantes()->create([
            'color' => 'Azul Original',
            'precio' => 180000,
            'disponible' => true,
            'orden' => 0,
        ]);

        $variant2 = $product->variantes()->create([
            'color' => 'Rojo a eliminar',
            'precio' => 190000,
            'disponible' => true,
            'orden' => 1,
        ]);

        // Enviar actualización: modificar variant1, agregar variant3 nueva, omitir variant2 (debe borrarse)
        $updatePayload = [
            'name' => 'Zapato Editado',
            'audience' => 'mujer',
            'category' => 'zapatos',
            'variants' => [
                [
                    'id' => $variant1->id,
                    'color' => 'Azul Actualizado',
                    'price' => 195000,
                    'available' => true,
                ],
                [
                    'color' => 'Verde Nuevo Estilo',
                    'price' => 200000,
                    'available' => true,
                ]
            ]
        ];

        $response = $this->putJson("/api/productos/{$product->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Zapato Editado')
            ->assertJsonCount(2, 'data.variants')
            ->assertJsonPath('data.variants.0.color', 'Azul Actualizado')
            ->assertJsonPath('data.variants.1.color', 'Verde Nuevo Estilo');

        $this->assertDatabaseHas('variantes', [
            'id' => $variant1->id,
            'color' => 'Azul Actualizado',
        ]);

        $this->assertDatabaseMissing('variantes', [
            'id' => $variant2->id,
        ]);
    }

    /**
     * Test: Eliminar producto y cascada de variantes (DELETE /api/productos/{id})
     */
    public function test_can_delete_product_with_cascade(): void
    {
        $this->authenticateAsAdmin();

        $product = Product::create([
            'nombre' => 'Producto Para Borrar',
            'publico' => 'hombre',
            'categoria' => 'gorras',
            'activo' => true,
        ]);

        $variant = $product->variantes()->create([
            'color' => 'Negro',
            'precio' => 80000,
            'disponible' => true,
            'orden' => 0,
        ]);

        $response = $this->deleteJson("/api/productos/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Producto eliminado correctamente.');

        $this->assertDatabaseMissing('productos', [
            'id' => $product->id,
        ]);

        $this->assertDatabaseMissing('variantes', [
            'id' => $variant->id,
        ]);
    }

    /**
     * Test: Validación de datos requeridos al crear
     */
    public function test_validation_fails_on_missing_required_fields(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->postJson('/api/productos', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'categoria', 'variantes']);
    }

    /**
     * Test: Las consultas públicas solo devuelven productos activos
     */
    public function test_public_catalog_only_returns_active_products(): void
    {
        $activeProduct = Product::create([
            'nombre' => 'Producto Activo Visible',
            'publico' => 'mujer',
            'categoria' => 'zapatos',
            'activo' => true,
        ]);
        $activeProduct->variantes()->create([
            'color' => 'Rosa',
            'precio' => 150000,
            'disponible' => true,
        ]);

        $inactiveProduct = Product::create([
            'nombre' => 'Producto Oculto Inactivo',
            'publico' => 'mujer',
            'categoria' => 'zapatos',
            'activo' => false,
        ]);
        $inactiveProduct->variantes()->create([
            'color' => 'Gris',
            'precio' => 120000,
            'disponible' => false,
        ]);

        // Petición pública GET /api/productos (sin autenticación)
        $response = $this->getJson('/api/productos');

        $response->assertStatus(200);
        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($activeProduct->id, $returnedIds);
        $this->assertNotContains($inactiveProduct->id, $returnedIds);

        // Consultar directamente el producto inactivo siendo público debe retornar 404
        $this->getJson("/api/productos/{$inactiveProduct->id}")->assertStatus(404);
    }

    /**
     * Test: El administrador autenticado puede ver tanto productos activos como inactivos
     */
    public function test_authenticated_admin_can_view_all_products_including_inactive(): void
    {
        $this->authenticateAsAdmin();

        $inactiveProduct = Product::create([
            'nombre' => 'Producto Inactivo Para Admin',
            'publico' => 'hombre',
            'categoria' => 'camisas',
            'activo' => false,
        ]);
        $inactiveProduct->variantes()->create([
            'color' => 'Negro',
            'precio' => 90000,
            'disponible' => false,
        ]);

        $response = $this->getJson('/api/productos');

        $response->assertStatus(200);
        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($inactiveProduct->id, $returnedIds);

        // Consultar directamente el producto inactivo como admin debe retornar 200
        $this->getJson("/api/productos/{$inactiveProduct->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $inactiveProduct->id)
            ->assertJsonPath('data.active', false);
    }

    /**
     * Test: Validación rechaza precios negativos en las variantes
     */
    public function test_validation_rejects_negative_prices_for_variants(): void
    {
        $this->authenticateAsAdmin();

        $payload = [
            'name' => 'Producto Precio Inválido',
            'audience' => 'mujer',
            'category' => 'zapatos',
            'variants' => [
                [
                    'color' => 'Rojo',
                    'price' => -15000,
                    'available' => true,
                ]
            ]
        ];

        $response = $this->postJson('/api/productos', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['variantes.0.precio']);
    }
}
