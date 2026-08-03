<?php

namespace App\Models;

use App\Models\Concerns\ResolvesPublicAssetUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    use HasFactory;
    use ResolvesPublicAssetUrl;

    /**
     * Campos permitidos para asignaciÃ³n masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'button_text',
        'button_url',
        'open_in_new_tab',
        'desktop_image',
        'mobile_image',
        'sort_order',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /**
     * ConversiÃ³n automÃ¡tica de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Estado calculado de la diapositiva.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->is_active) {
                    return 'inactive';
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

                return 'visible';
            }
        );
    }

    /**
     * Indica si debe mostrarse actualmente en la tienda.
     */
    protected function isVisible(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === 'visible'
        );
    }

    /**
     * URL pública de la imagen de escritorio.
     */
    protected function desktopImageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): ?string =>
                $this->resolvePublicAssetUrl(
                    $this->attributes['desktop_image'] ?? null
                )
        );
    }

    /**
     * URL pública de la imagen móvil.
     */
    protected function mobileImageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (): ?string =>
                $this->resolvePublicAssetUrl(
                    $this->attributes['mobile_image'] ?? null
                )
        );
    }}