<?php

namespace App\Models;

use App\Models\Concerns\ResolvesPublicAssetUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;
    use ResolvesPublicAssetUrl;

    /**
     * Campos permitidos para asignaciÃ³n masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'sort_order',
        'is_active',
    ];

    /**
     * Conversiones automÃ¡ticas de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Productos que pertenecen a la marca.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * URL pública del logotipo.
     */
    protected function logoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): ?string =>
                $this->resolvePublicAssetUrl(
                    $this->attributes['logo'] ?? null
                )
        );
    }}