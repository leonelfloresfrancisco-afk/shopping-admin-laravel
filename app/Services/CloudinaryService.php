<?php

declare(strict_types=1);

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            throw new RuntimeException(
                'Las credenciales de Cloudinary no están configuradas.'
            );
        }

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * @return array{
     *     url: string,
     *     public_id: string,
     *     width: int|null,
     *     height: int|null,
     *     format: string|null
     * }
     */
    public function uploadImage(
        UploadedFile|string $file,
        string $folder
    ): array {
        $filePath = $file instanceof UploadedFile
            ? $file->getRealPath()
            : $file;

        if (! $filePath) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }

        $result = $this->cloudinary
            ->uploadApi()
            ->upload($filePath, [
                'folder' => $folder,
                'resource_type' => 'image',
                'overwrite' => false,
            ]);

        return [
            'url' => (string) $result['secure_url'],
            'public_id' => (string) $result['public_id'],
            'width' => isset($result['width'])
                ? (int) $result['width']
                : null,
            'height' => isset($result['height'])
                ? (int) $result['height']
                : null,
            'format' => $result['format'] ?? null,
        ];
    }

    public function deleteImage(?string $publicId): void
    {
        if (! $publicId) {
            return;
        }

        $this->cloudinary
            ->uploadApi()
            ->destroy($publicId, [
                'resource_type' => 'image',
                'invalidate' => true,
            ]);
    }
}