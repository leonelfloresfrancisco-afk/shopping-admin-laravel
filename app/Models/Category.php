<?php

namespace App\Models;

use App\Models\Concerns\ResolvesPublicAssetUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use ResolvesPublicAssetUrl;

    protected $fillable = [

        'name',

        'slug',

        'description',

        'image',

        'sort_order',

        'is_active',

        'meta_title',

        'meta_description',

    ];


    protected $casts = [

        'is_active' => 'boolean',

    ];


    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * URL pública de la imagen de la categoría.
     */
    protected function imageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): ?string =>
                $this->resolvePublicAssetUrl(
                    $this->attributes['image'] ?? null
                )
        );
    }}