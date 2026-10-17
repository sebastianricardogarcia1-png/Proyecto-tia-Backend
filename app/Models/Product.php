<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo en MySQL.
     */
    protected $table = 'productos';

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'nombre',
        'publico',
        'categoria',
        'descripcion',
        'es_nuevo',
        'es_destacado',
        'activo',
        'referencia',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'es_nuevo' => 'boolean',
            'es_destacado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Relación 1:N con las variantes del producto.
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'producto_id');
    }
}
