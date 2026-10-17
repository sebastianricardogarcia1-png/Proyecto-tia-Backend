<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variants = $this->relationLoaded('variantes') ? $this->variantes : collect();
        $mainVariant = $variants->first();
        $isAnyAvailable = $variants->isEmpty() ? true : $variants->contains('disponible', true);

        return [
            'id' => $this->id,
            'name' => $this->nombre,
            'audience' => $this->publico,
            'category' => $this->categoria,
            'description' => $this->descripcion ?? '',
            'isNew' => (bool) $this->es_nuevo,
            'isFeatured' => (bool) $this->es_destacado,
            'active' => (bool) $this->activo,
            'reference' => $this->referencia ?? ($mainVariant?->referencia ?? ''),
            'price' => (int) ($mainVariant?->precio ?? 0),
            'imageUrl' => $mainVariant?->imagen_url ?? '',
            'available' => (bool) $isAnyAvailable,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'variants' => ProductVariantResource::collection($variants),
        ];
    }
}
