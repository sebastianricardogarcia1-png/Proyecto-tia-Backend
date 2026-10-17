<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Listar todos los productos con sus variantes ordenadas.
     * Las consultas públicas solo devuelven productos activos.
     * Los administradores autenticados pueden ver todos los productos.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['variantes' => fn ($q) => $q->orderBy('orden', 'asc')]);

        $user = auth('sanctum')->user();
        $isAdmin = $user && $user->is_admin;

        if (! $isAdmin) {
            $query->where('activo', true);
        } elseif ($request->has('activo')) {
            $query->where('activo', filter_var($request->query('activo'), FILTER_VALIDATE_BOOLEAN));
        }

        $products = $query->orderBy('created_at', 'desc')->get();

        return ProductResource::collection($products);
    }

    /**
     * Crear un nuevo producto junto con sus variantes.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated) {
            $productData = [
                'nombre' => $validated['nombre'],
                'publico' => $validated['publico'],
                'categoria' => $validated['categoria'],
                'descripcion' => $validated['descripcion'] ?? null,
                'es_nuevo' => $validated['es_nuevo'] ?? false,
                'es_destacado' => $validated['es_destacado'] ?? false,
                'activo' => $validated['activo'] ?? true,
                'referencia' => $validated['referencia'] ?? null,
            ];

            $product = Product::create($productData);

            $variantsData = array_map(function ($variant, $index) {
                return [
                    'color' => $variant['color'],
                    'precio' => $variant['precio'],
                    'imagen_url' => $variant['imagen_url'] ?? null,
                    'disponible' => $variant['disponible'] ?? true,
                    'referencia' => $variant['referencia'] ?? null,
                    'orden' => $variant['orden'] ?? $index,
                ];
            }, $validated['variantes'], array_keys($validated['variantes']));

            $product->variantes()->createMany($variantsData);

            return $product->load(['variantes' => fn ($q) => $q->orderBy('orden', 'asc')]);
        });

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Obtener el detalle de un producto específico.
     * Las consultas públicas solo pueden consultar productos activos.
     */
    public function show(int $id): ProductResource
    {
        $query = Product::with(['variantes' => fn ($q) => $q->orderBy('orden', 'asc')]);

        $user = auth('sanctum')->user();
        $isAdmin = $user && $user->is_admin;

        if (! $isAdmin) {
            $query->where('activo', true);
        }

        $product = $query->findOrFail($id);

        return new ProductResource($product);
    }

    /**
     * Actualizar un producto existente y sincronizar sus variantes.
     */
    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $validated = $request->validated();
        $product = Product::findOrFail($id);

        DB::transaction(function () use ($product, $validated) {
            $productData = [];
            foreach (['nombre', 'publico', 'categoria', 'descripcion', 'es_nuevo', 'es_destacado', 'activo', 'referencia'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $productData[$field] = $validated[$field];
                }
            }

            if (!empty($productData)) {
                $product->update($productData);
            }

            // Sincronizar variantes si vienen en el payload
            if (isset($validated['variantes']) && is_array($validated['variantes'])) {
                $existingVariantIds = $product->variantes()->pluck('id')->toArray();
                $keptVariantIds = [];

                foreach ($validated['variantes'] as $index => $variantInput) {
                    $variantPayload = [
                        'color' => $variantInput['color'],
                        'precio' => $variantInput['precio'],
                        'imagen_url' => $variantInput['imagen_url'] ?? null,
                        'disponible' => $variantInput['disponible'] ?? true,
                        'referencia' => $variantInput['referencia'] ?? null,
                        'orden' => $variantInput['orden'] ?? $index,
                    ];

                    $variantId = $variantInput['id'] ?? null;

                    if ($variantId && in_array($variantId, $existingVariantIds)) {
                        ProductVariant::where('id', $variantId)
                            ->where('producto_id', $product->id)
                            ->update($variantPayload);
                        $keptVariantIds[] = (int) $variantId;
                    } else {
                        $newVariant = $product->variantes()->create($variantPayload);
                        $keptVariantIds[] = $newVariant->id;
                    }
                }

                // Eliminar variantes que ya no están presentes en la petición
                $variantsToDelete = array_diff($existingVariantIds, $keptVariantIds);
                if (!empty($variantsToDelete)) {
                    ProductVariant::where('producto_id', $product->id)
                        ->whereIn('id', $variantsToDelete)
                        ->delete();
                }
            }
        });

        $product->load(['variantes' => fn ($q) => $q->orderBy('orden', 'asc')]);

        return new ProductResource($product);
    }

    /**
     * Eliminar un producto y sus variantes asociadas.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
