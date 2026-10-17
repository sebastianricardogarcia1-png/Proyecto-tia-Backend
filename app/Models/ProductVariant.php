<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo en MySQL.
     */
    protected $table = 'variantes';

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'producto_id',
        'color',
        'precio',
        'imagen_url',
        'disponible',
        'referencia',
        'orden',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'precio' => 'integer',
            'disponible' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /**
     * Relación inversa: la variante pertenece a un producto.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
}
