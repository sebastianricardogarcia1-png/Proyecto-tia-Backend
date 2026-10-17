<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->producto_id,
            'color' => $this->color ?? 'Estilo Principal',
            'price' => (int) ($this->precio ?? 0),
            'imageUrl' => $this->imagen_url ?? '',
            'available' => (bool) $this->disponible,
            'reference' => $this->referencia ?? '',
            'order' => (int) ($this->orden ?? 0),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
