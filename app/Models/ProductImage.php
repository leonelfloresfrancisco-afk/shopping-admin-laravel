<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',

        /*
        |--------------------------------------------------------------------------
        | Almacenamiento de imagen
        |--------------------------------------------------------------------------
        |
        | "image" guarda la ruta local anterior o la URL completa de Cloudinary.
        | "image_public_id" permite eliminar o reemplazar el recurso remoto.
        | "image_provider" identifica el proveedor utilizado.
        |
        */

        'image',
        'image_public_id',
        'image_provider',

        'alt_text',
        'sort_order',
        'is_active',
    ];

    /**
     * Conversión automática de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Producto al que pertenece esta imagen.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}