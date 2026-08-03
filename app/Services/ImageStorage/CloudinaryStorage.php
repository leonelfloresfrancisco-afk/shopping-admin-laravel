<?php

declare(strict_types=1);

namespace App\Services\ImageStorage;

use App\Contracts\ImageStorage;
use App\Services\CloudinaryService;
use Illuminate\Http\UploadedFile;

final class CloudinaryStorage implements ImageStorage
{
    /**
     * Crear el adaptador de almacenamiento de Cloudinary.
     */
    public function __construct(
        private readonly CloudinaryService $cloudinaryService
    ) {
    }

    /**
     * Subir una imagen a Cloudinary.
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
    ): array {
        $result = $this->cloudinaryService->uploadImage(
            $file,
            $folder
        );

        return [
            'url' => $result['url'],
            'public_id' => $result['public_id'],
            'provider' => 'cloudinary',
            'width' => $result['width'],
            'height' => $result['height'],
            'format' => $result['format'],
        ];
    }

    /**
     * Eliminar una imagen de Cloudinary.
     */
    public function delete(
        ?string $publicId,
        ?string $url = null
    ): void {
        $this->cloudinaryService->deleteImage(
            $publicId
        );
    }
}