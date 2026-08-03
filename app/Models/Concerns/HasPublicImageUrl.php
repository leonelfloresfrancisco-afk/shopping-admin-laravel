<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

trait HasPublicImageUrl
{
    /**
     * Devuelve la URL pública correcta de la imagen.
     *
     * Admite:
     * - URLs completas de Cloudinary.
     * - Rutas antiguas del almacenamiento público de Laravel.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $image = $this->attributes['image'] ?? null;

                if (
                    ! is_string($image)
                    || trim($image) === ''
                ) {
                    return null;
                }

                $image = trim($image);

                /*
                 * La imagen ya tiene una URL completa,
                 * por ejemplo una URL de Cloudinary.
                 */
                if (
                    Str::startsWith(
                        $image,
                        [
                            'http://',
                            'https://',
                        ]
                    )
                ) {
                    return $image;
                }

                /*
                 * Compatibilidad con imágenes locales antiguas.
                 */
                $relativePath = ltrim(
                    str_replace(
                        '\\',
                        '/',
                        $image
                    ),
                    '/'
                );

                return asset(
                    'storage/' . $relativePath
                );
            }
        );
    }
}