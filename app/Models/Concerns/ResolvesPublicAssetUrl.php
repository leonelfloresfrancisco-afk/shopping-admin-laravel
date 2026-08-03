<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait ResolvesPublicAssetUrl
{
    /**
     * Devuelve una URL pública válida para recursos locales o remotos.
     */
    protected function resolvePublicAssetUrl(
        mixed $value
    ): ?string {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim(
            str_replace('\\', '/', $value)
        );

        /*
         * Corrige valores como:
         * /storage/https://res.cloudinary.com/...
         */
        if (
            Str::startsWith(
                $value,
                [
                    '/storage/http://',
                    '/storage/https://',
                ]
            )
        ) {
            return Str::after(
                $value,
                '/storage/'
            );
        }

        $absoluteStoragePrefix =
            rtrim(url('/storage'), '/') . '/';

        if (
            Str::startsWith(
                $value,
                [
                    $absoluteStoragePrefix . 'http://',
                    $absoluteStoragePrefix . 'https://',
                ]
            )
        ) {
            return Str::after(
                $value,
                $absoluteStoragePrefix
            );
        }

        /*
         * Cloudinary u otra URL remota.
         */
        if (
            Str::startsWith(
                $value,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            return $value;
        }

        /*
         * Archivo local antiguo.
         */
        $relativePath = ltrim(
            $value,
            '/'
        );

        if (Str::startsWith($relativePath, 'storage/')) {
            $relativePath = Str::after(
                $relativePath,
                'storage/'
            );
        }

        return asset(
            'storage/' . $relativePath
        );
    }
}