<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface ImageStorage
{
    /**
     * Sube una imagen y devuelve sus datos de almacenamiento.
     *
     * @return array{
     *     url: string,
     *     public_id: string|null,
     *     provider: string,
     *     width: int|null,
     *     height: int|null,
     *     format: string|null
     * }
     */
    public function upload(
        UploadedFile|string $file,
        string $folder
    ): array;

    /**
     * Elimina una imagen del proveedor correspondiente.
     */
    public function delete(
        ?string $publicId,
        ?string $url = null
    ): void;
}