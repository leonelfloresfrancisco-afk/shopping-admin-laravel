<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\ImageStorage;
use App\Models\Brand;
use App\Models\CarouselSlide;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class MigrateBrandAndCarouselImagesToCloudinary extends Command
{
    protected $signature = 'images:migrate-brand-carousel';

    protected $description =
        'Migra logos de marcas e imágenes del carrusel a Cloudinary.';

    public function handle(
        ImageStorage $imageStorage
    ): int {
        $disk = Storage::disk('public');

        $this->info('Migrando logos de marcas...');

        Brand::query()
            ->whereNotNull('logo')
            ->where('logo', '!=', '')
            ->orderBy('id')
            ->each(function (Brand $brand) use (
                $disk,
                $imageStorage
            ): void {
                $logo = $brand->getRawOriginal('logo');

                if (
                    ! is_string($logo)
                    || trim($logo) === ''
                    || Str::startsWith(
                        $logo,
                        [
                            'http://',
                            'https://',
                        ]
                    )
                ) {
                    return;
                }

                $path = ltrim(
                    str_replace('\\', '/', $logo),
                    '/'
                );

                if (! $disk->exists($path)) {
                    $this->warn(
                        "Logo no encontrado: {$brand->name} ({$path})"
                    );

                    return;
                }

                try {
                    $upload = $imageStorage->upload(
                        $disk->path($path),
                        'wasyntek/brands'
                    );

                    $brand->forceFill([
                        'logo' => $upload['url'],
                    ])->saveOrFail();

                    $this->info(
                        "Logo migrado: {$brand->name}"
                    );
                } catch (Throwable $exception) {
                    report($exception);

                    $this->error(
                        "Error en marca {$brand->name}: "
                        . $exception->getMessage()
                    );
                }
            });

        $this->info('Migrando imágenes del carrusel...');

        CarouselSlide::query()
            ->orderBy('id')
            ->each(function (CarouselSlide $slide) use (
                $disk,
                $imageStorage
            ): void {
                $updates = [];

                foreach (
                    [
                        'desktop_image' => 'wasyntek/carousel/desktop',
                        'mobile_image' => 'wasyntek/carousel/mobile',
                    ] as $field => $folder
                ) {
                    $value = $slide->getRawOriginal($field);

                    if (
                        ! is_string($value)
                        || trim($value) === ''
                        || Str::startsWith(
                            $value,
                            [
                                'http://',
                                'https://',
                            ]
                        )
                    ) {
                        continue;
                    }

                    $path = ltrim(
                        str_replace('\\', '/', $value),
                        '/'
                    );

                    if (! $disk->exists($path)) {
                        $this->warn(
                            "Archivo no encontrado: slide #{$slide->id} {$field} ({$path})"
                        );

                        continue;
                    }

                    try {
                        $upload = $imageStorage->upload(
                            $disk->path($path),
                            $folder
                        );

                        $updates[$field] = $upload['url'];
                    } catch (Throwable $exception) {
                        report($exception);

                        $this->error(
                            "Error en slide #{$slide->id} {$field}: "
                            . $exception->getMessage()
                        );
                    }
                }

                if ($updates !== []) {
                    $slide->forceFill($updates)->saveOrFail();

                    $this->info(
                        "Carrusel migrado: slide #{$slide->id}"
                    );
                }
            });

        $this->newLine();
        $this->info('Migración terminada.');

        return self::SUCCESS;
    }
}