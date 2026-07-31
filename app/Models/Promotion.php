<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'applies_to',
        'minimum_purchase',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
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
            'discount_value' => 'decimal:2',
            'minimum_purchase' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relación muchos-a-muchos con categorías.
     *
     * Esta relación utiliza la tabla intermedia:
     * category_promotion
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_promotion',
            'promotion_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * Relación muchos-a-muchos con productos.
     *
     * Esta relación utiliza la tabla intermedia:
     * product_promotion
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_promotion',
            'promotion_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * Filtra promociones disponibles en el momento actual.
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $dateQuery) use ($now): void {
                $dateQuery
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $dateQuery) use ($now): void {
                $dateQuery
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->where(function (Builder $usageQuery): void {
                $usageQuery
                    ->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    /**
     * Estado calculado de la promoción.
     *
     * Posibles valores:
     * active, scheduled, expired, exhausted o inactive.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->is_active) {
                    return 'inactive';
                }

                if (
                    $this->usage_limit !== null
                    && $this->used_count >= $this->usage_limit
                ) {
                    return 'exhausted';
                }

                if (
                    $this->starts_at !== null
                    && $this->starts_at->isFuture()
                ) {
                    return 'scheduled';
                }

                if (
                    $this->ends_at !== null
                    && $this->ends_at->isPast()
                ) {
                    return 'expired';
                }

                return 'active';
            }
        );
    }

    /**
     * Texto formateado del descuento.
     *
     * Ejemplos:
     * 20%
     * S/ 50
     */
    protected function discountLabel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $value = (float) $this->discount_value;

                $formattedValue = floor($value) === $value
                    ? number_format($value, 0)
                    : number_format($value, 2);

                if ($this->discount_type === 'percentage') {
                    return "{$formattedValue}%";
                }

                return "S/ {$formattedValue}";
            }
        );
    }
}