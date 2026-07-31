<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'sku',
        'name',
        'slug',
        'description',
        'cost_price',
        'price',
        'compare_at_price',
        'stock',

        /*
        |--------------------------------------------------------------------------
        | Imagen principal
        |--------------------------------------------------------------------------
        |
        | Este campo se conserva para no romper los productos existentes.
        | Las fotografías adicionales se guardarán en product_images.
        |
        */

        'image',

        'is_active',
        'is_featured',
    ];

    /**
     * Conversión automática de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'brand_id' => 'integer',
            'cost_price' => 'decimal:2',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Categoría a la que pertenece el producto.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Marca a la que pertenece el producto.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Todas las imágenes adicionales del producto.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Imágenes adicionales activas para la tienda pública.
     */
    public function activeImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Promociones asignadas directamente al producto.
     */
    public function promotions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Promotion::class,
            'product_promotion',
            'product_id',
            'promotion_id'
        )->withTimestamps();
    }

    /**
     * Determina si el producto tiene un descuento válido.
     */
    protected function hasDiscount(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->compare_at_price !== null
                && (float) $this->compare_at_price > (float) $this->price
        );
    }

    /**
     * Calcula el porcentaje de descuento.
     */
    protected function discountPercentage(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $previousPrice = (float) $this->compare_at_price;
                $currentPrice = (float) $this->price;

                if (
                    $previousPrice <= 0
                    || $previousPrice <= $currentPrice
                ) {
                    return 0;
                }

                return (int) round(
                    (($previousPrice - $currentPrice) / $previousPrice) * 100
                );
            }
        );
    }

    /**
     * Calcula la ganancia unitaria del producto.
     */
    protected function unitProfit(): Attribute
    {
        return Attribute::make(
            get: fn (): float =>
                (float) $this->price
                - (float) $this->cost_price
        );
    }

    /**
     * Calcula el margen porcentual sobre el precio de venta.
     */
    protected function profitMargin(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $price = (float) $this->price;

                if ($price <= 0) {
                    return 0;
                }

                return round(
                    (
                        (
                            $price
                            - (float) $this->cost_price
                        )
                        / $price
                    ) * 100,
                    2
                );
            }
        );
    }
}